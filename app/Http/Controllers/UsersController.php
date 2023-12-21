<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Store;
use App\Models\Area;
use App\Models\UserStores;
use App\Models\Apps;
use App\Models\UserApps;
use App\Models\UserRol;
use App\Models\UserModules;
use App\Models\UserStates;
use App\Models\UserLog;
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
        $branches = Store::all();
        $position = UserRol::all();
        $area = Area::all();
        $status = UserStates::all();
        // $users = User::with('area')->get();
        if($users){
            $res = [
                "usuarios"=>$users,
                "branches"=>$branches,
                "position"=>$position,
                "area"=>$area,
                "status"=>$status
            ];
            return response()->json($res,200);
        }else{
            return response()->json("No hay ningun Usuario",404);
        }
    }

    public function getIndex(Request $request){
        $roles = Area::with('roles')->get();
        $workpoints = Store::select('id as value','name as label','alias')->get();
        $allwork = Store::all();
        $app = Apps::select('id as value','name as label','name')->get();
        $usuarios = User::all();
        $res = [
            "roles"=>$roles,
            "workpoints"=>$workpoints,
            "app"=>$app,
            "namework"=>$allwork,
            "usuarios"=>$usuarios
        ];
        return response()->json($res,200);
    }

    public function addUser(Request $request){
        $device = $request->ip();
        $account = $request->user;
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
                         $app = new UserApps();
                         if($request->apps){
                            foreach($request->apps as $api){
                                $insapp[] = [
                                    "_user"=> $res['id'],
                                    "_app"=>$api,
                                ];
                             }
                             $app->insert($insapp);
                         }

                         //user_storest
                         $stores = UserStores::where('_user',$res['id'])->whereIn('_store',$request->stores);
                         $stores->update(['_state'=>1]);

                        //user_permissions
                        $useper = new UserModules;
                        $permissions = UserRol::with('permissions')->where('id',$request->_rol)->first();
                        $permi = $permissions['permissions'];
                        foreach($permi as $pre){
                            $inserper[] = [
                                "_user"=>$res['id'],
                                "_permission"=>$pre['_permission'],
                                "_module"=>$pre['_module']
                            ];
                        }
                        $useper->insert($inserper);

                        $inslog = new UserLog();
                        $inslog->_user = $account;
                        $inslog->_type_log = 1;
                        $inslog->details = json_encode([
                            "at"=>now()->format('Y-m-d H:m:s'),
                            "device"=>$device,
                            "account"=>["id"=>$res['id'], "nick"=>$res['nick']]
                        ]);
                        $inslog->save();

                        return response()->json($res,200);
                    }else{
                        return response()->json('No se pudo crear el usuario',500);
                    }
                }
            }
        }
    }

    public function getUserWorkpoint(){

        $users = User::with('rol','rol.area')->whereHas('rol.area', function($q){
            $q->whereIn('id',[15,16,17]);
        })->get();
        $branches = Store::whereNotIn('id',[17,100])->get();

        $res = [
            "users"=>$users,
            "branches"=>$branches
        ];
        return response()->json($res,200);
    }

    public function changeWork(Request $request){
        $store = $request->store;
        $user = $request->user;
        $chus = User::find($user);
        $wkp = $chus->_store;

        $upd = UserStores::where('_user',$user)->where('_store',$wkp)->update(['_state'=>2]);
        if($upd){
            $updn = UserStores::where('_user',$user)->where('_store',$store)->update(['_state'=>1]);
            if($updn){
                $updu = User::where('id',$user)->update(['_store'=>$store]);
                if($updu){
                    $res = "Cambio Usuario Realizado";
                    return response()->json($res,200);
                }else{
                    $res = "No se pudo actualizar el usuario";
                    return response()->json($res,404);
                }
            }else{
                $res = "No se pudo actualizar el usuario";
                return response()->json($res,404);
            }
        }else {
            $res = "No se pudo actualizar el usuario";
            return response()->json($res,404);
        }
    }
}
