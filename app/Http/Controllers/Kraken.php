<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class Kraken extends Controller
{
    public function trySignin(Request $request){
        $nick = $request->nick; // recibe el nick
        $pass = $request->pass; // recibe el pass
        $device = $request->ip();

        /**
         * Se realiza la busqueda de una cuenta en base a
         * nick, correo y/o telefono movil
         */
        $user = User::where('nick',$nick)->orWhere('celphone',$nick)->orWhere('email',$nick)->first();

        if(($nick&&$pass)&&$user&&Hash::check($pass,$user->password)){

            $user->load([
                'rol',
                'state',
                'store',
                'stores',
                'modules' => fn($q) => $q->with([ 'permission', 'module' ])
            ]);

            // se mapean las filas de los modulos para parsear a un objeto los detalles del modulo (icono etc...)
            // $user->modules->map(function($m){ $m->module->details = json_decode($m->module->details); return $m; });

            if($user->_state<=2){
                $datafortoken = [ "uid"=>$user->id, "rol"=>$user->_rol ]; // data que se encrypta en el token autenticador
                $token = $this->genToken($datafortoken);// genera el token con el rol y el id del usuario + fecha de creacion + fecha de expiracion (tiempo que es valido el token)

                /** si el status es 2 agregar al usuario el log de inicio de sesion */
                if($user->_state==2){
                    $details = [ "device"=>$device, "at"=>Carbon::now()->format("Y-m-d h:i:s") ];
                    UserLog::create([ "_user"=>$user->id, "_type_log"=>2, "details"=>json_encode($details) ]);
                }

                return response()->json([ "account"=>$user, "token"=>$token, "ip"=>$device ]);
            } return response()->json([ "state"=>$user->state], 401 );// cuenta bloqueada o archivada
        } return response("credenciales erroneas!", 404);// password incorrecto
    }

    private function genToken($data){
        $data["ini"] = Carbon::now();
        $data["exp"] = Carbon::now()->add(3,"day");
        return Crypt::encryptString(json_encode($data));
    }

    public function firstLogin(Request $request){
        $uid = $request->fixeds->uid;
        $device = $request->ip();
        $newPass = Hash::make($request->newpass);
        $details = [ "device"=>$device, "at"=>Carbon::now()->format("Y-m-d h:i:s") ];

        $user = User::with([
            'rol',
            'state',
            'store',
            'stores'
            // 'modules'=> fn($q) => $q->with([ 'permission', 'module' ])
        ])->find($uid);

        $user->password = $newPass;
        $user->_state = 2;
        $user->change_password = 0;
        $user->save();

        $log = UserLog::create([
            "_user" => $uid,
            "_type_log" => 2,
            "details" => json_encode($details)
        ]);

        $user->refresh();

        return response()->json(["user"=>$user]);
    }

    public function joinat(Request $request){
        return response()->json($request->all());
    }
}
