<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Store;
use App\Models\Area;
use App\Models\UserStores;
use App\Models\Apps;
use App\Models\UserRol;
use App\Models\UserModules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    public function fullReset(Request $request){

        $_accounts = $request->accounts;
        $log = [];

        if(gettype($_accounts)=="array" && sizeof($_accounts)>0){

            $accounts = User::whereIn("id",$_accounts)->orWhereIn("nick",$_accounts)->get();

            foreach($accounts as $acc){
                try {

                    $unsetLogs = DB::table('user_logs')->where("_user",$acc->id)->delete();
                    $reset = DB::table('users')->where("id",$acc->id)->update(["change_password"=>1, "_state"=>1]);
                    $log[] = [ "account"=>"{$acc->id}::{$acc->nick}", "logremove"=>$unsetLogs, "reset"=>$reset ];

                } catch (\Throwable $th) { $log[] = [ "error"=>$th->getMessage() ]; }
            }

            return response()->json($log);
        }else{
            return response("Meriyein: asegurate de enviar un array valido y no vacio", 400);
        }
    }

    public function getUsers(Request $request){
        $users = User::with('store:id,name','rol.area')->get();
        // $users = User::with('area')->get();
        if($users){
            return response()->json($users,200);
        }else{
            return response()->json("No hay ningun Usuario",404);
        }
    }

    public function getIndex(Request $request){
        $roles = Area::with('roles')->get();
        $workpoints = Store::select('id as value','name as label','alias')->get();
        $allwork = Store::all();
        $app = App::select('id as value','name as label','name')->get();
        $res = [
            "roles"=>$roles,
            "workpoints"=>$workpoints,
            "app"=>$app,
            "namework"=>$allwork
        ];
        return response()->json($res,200);
    }

    public function addUser(Request $request){
        $nick = $request->nick;
        $celphone = str_replace('-','',$request->celphone);
        $exist = User::where('nick',$nick)->get();
        if(count($exist) > 0){
            return response()->json('El nick ya existe',401);
        }else {
            $existcel = User::where('celphone',$celphone)->get();
            if(count($existcel) > 0){
                return response()->json('El telefono ya existe',401);
            }else{
                $existem = User::where('email',$request->email)->get();
                if(count($existem) > 0){
                    return response()->json('El email ya existe',401);
                }else{
                    $user = new User();//se inserta el usuario
                    $user->name = $request->name;
                    $user->surnames = $request->surnames;
                    $user->dob = $request->dob;
                    $user->celphone = $celphone;
                    $user->nick = $request->nick;
                    $user->password = Hash::make('12345');
                    $user->change_password = 1;
                    $user->email = $request->email;
                    $user->gender = $request->gender;
                    $user->_rol = $request->_rol;
                    $user->_state = 1;
                    $user->_store = $request->_store;
                    $user->save();
                    $res = $user->fresh()->toArray();
                    if($res){
                         //userapps
                        //  $app = new App();
                        //  foreach($request->apps as $api){
                        //     $app->_user = $res['id'];
                        //     $app->_app = $api;
                        //     $api->save();
                        //  }
                         //user_storest
                         $stores = UserStores::where('_user',1)->whereIn('_store',$request->stores);
                         $stores->update(['_state'=>1]);

                        //user_permissions
                        $permissions = UserRol::with('permissions')->where('id',$request->_rol)->first();
                        $permi = $permissions['permissions'];
                        $useper = new UserModules;
                        foreach($permi as $pre){
                            $useper->_user = $res['id'];
                            $useper->_permission = $pre['_permission'];
                            $useper->_module = $pre['_module'];
                            $useper->save();
                        }

                        return response()->json($res,200);
                    }else{
                        return response()->json('No se pudo crear el usuario',500);
                    }
                }
            }
        }
    }
}
