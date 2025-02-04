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
use App\Models\ModuleApp;
use App\Models\Permission;
use App\Models\UserStates;
use App\Models\UserLog;
use App\Models\RolDefaultPermission;
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
        $users = User::with('store:id,name','rol.area','state','useStore','apps')->get();
        $branches = Store::all();
        $position = UserRol::with('area')->get();
        $area = Area::all();
        $app = Apps::select('id as value','name as label','name')->get();
        $status = UserStates::all();
        $workpoints = Store::select('id as value','name as label','alias', 'name')->get();
        if($users){
            $res = [
                "usuarios"=>$users,
                "branches"=>$branches,
                "position"=>$position,
                "area"=>$area,
                "status"=>$status,
                "workpoints"=>$workpoints,
                "apps"=>$app
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
        $celphone = str_replace('-', '', $request->celphone);

        $exist = User::where('nick', $nick)->get();
        if (count($exist) > 0) {
            return response()->json('El nick ya existe', 401);
        } else {
            $existcel = User::where('celphone', $celphone)->get();
            if (count($existcel) > 0) {
                return response()->json('El telefono ya existe', 401);
            } else {
                $existem = User::where('email', $request->email)->get();
                if (count($existem) > 0) {
                    return response()->json('El email ya existe', 401);
                } else {
                    $user = new User(); // Crear el usuario
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
                    if ($res) {
                        $uid = $res['id'];
                        $folderPath = public_path('multimedia/profiles/'.$uid.'/');
                        if (!file_exists($folderPath)) {
                            mkdir($folderPath, 0777, true);
                        }
                        if ($request->hasFile('avatar')) {
                            $avatar = $request->file('avatar');
                            $avatarPath = $folderPath . '/' . $avatar->getClientOriginalName();
                            $avatar->move($folderPath, $avatar->getClientOriginalName());
                            $user->avatar = $avatar->getClientOriginalName();
                            $user->save();
                        }
                        $apps = !empty($request->apps) ?  explode(',',$request->apps) : [];
                        if(count($apps) > 0){
                        $app = new UserApps();
                           foreach($apps as $api){
                               $insapp[] = [
                                   "_user"=> $res['id'],
                                   "_app"=>$api,
                               ];
                            }
                            $app->insert($insapp);
                        }

                        //user_storest
                        $stores = UserStores::where('_user',$res['id'])->whereIn('_store',explode(',',$request->stores));
                        $stores->update(['_state'=>1]);


                       //user_permissions
                       $useper = new UserModules;
                       $permissions = UserRol::with('permissions')->where('id',$request->_rol)->first();
                       if($permissions && $permissions->permissions->isNotEmpty()){
                        $permi = $permissions['permissions'];
                        foreach($permi as $pre){
                            $inserper[] = [
                                "_user"=>$res['id'],
                                "_permission"=>$pre['_permission'],
                                "_module"=>$pre['_module']
                            ];
                        }
                        $useper->insert($inserper);
                       }

                       $inslog = new UserLog();
                       $inslog->_user = $account;
                       $inslog->_type_log = 1;
                       $inslog->details = json_encode([
                           "at"=>now()->format('Y-m-d H:m:s'),
                           "device"=>$device,
                           "account"=>["id"=>$res['id'], "nick"=>$res['nick']]
                       ]);
                       $inslog->save();
                        return response()->json($res, 200);
                    } else {
                        return response()->json('No se pudo crear el usuario', 500);
                    }
                }
            }
        }
    }


    public function getUserWorkpoint(){

        // $users = User::with('store','rol','rol.area')->whereHas('rol.area', function($q){
        //     $q->whereIn('id',[15,16,17]);
        // })->get();
        $users = User::with(['store:id,name','rol.area','state','useStore','apps'])->get();
        $branches = Store::where('_state',1)->get();
        $position = UserRol::with('area')->get();
        $area = Area::all();
        $app = Apps::select('id as value','name as label','name')->get();
        $status = UserStates::all();
        $workpoints = Store::select('id as value','name as label','alias', 'name')->get();

        $res = [
            "users"=>$users,
            "branches"=>$branches,
            "position"=>$position,
            "area"=>$area,
            "status"=>$status,
            "workpoints"=>$workpoints,
            "apps"=>$app
        ];
        return response()->json($res,200);
    }

    public function changeWork(Request $request){
         // return $request->all();
         $device = $request->ip();
         $root = $request->fixeds->uid;
         $account = $request->user;
         $before = $account['store']['id'];
         $storenew = $request->store;
         $user = User::find($account['id']);
         $user->_rol = $account['rol']['id'];
         $user->_state = 5;
         $user->_store = $storenew;
         $user->save();
         $res = $user->fresh()->toArray();
         if($res){
             //userapps
             $app = new UserApps();
             if($account['apps']){
                 $deleted = UserApps::where('_user',$account['id'])->delete();
                 $app->insert($account['apps']);
             }
             //stores
             $stores = $account['use_store'];
              foreach($stores as $store){
                 $usestore = UserStores::where('_user',$account['id'])->where('_store',$store['_store']);
                 $usestore->update(['_state'=>$store['_state']]);
             }
             //permissions

             $useper = new UserModules;
             $delete = UserModules::where('_user',$account['id'])->delete();
             $permissions = UserRol::with('permissions')->where('id',$account['rol']['id'])->first();
             $permi = $permissions['permissions'];
             foreach($permi as $pre){
                 $inserper[] = [
                     "_user"=>$account['id'],
                     "_permission"=>$pre['_permission'],
                     "_module"=>$pre['_module']
                 ];
             }
             $useper->insert($inserper);

             $inslog = new UserLog();
             $inslog->_user = $root;
             $inslog->_type_log = 3;
             $inslog->details = json_encode([
                 "at"=>now()->format('Y-m-d H:m:s'),
                 "modification"=>"Se cambio la sucursal del usuario de ".$before." a ".$storenew,
                 "device"=>$device,
                 "account"=>["id"=>$account['id'], "nick"=>$account['nick'],"_state"=>$account['state']['id']]
             ]);
             $inslog->save();
             return response()->json($res,200);
         }else{
             return response()->json('No se logro modificar el usuario ',500);
         }
    }

    public function updateUser(Request $request){
        // return $request->all();
        $device = $request->ip();
        $account = $request->user;
        $state = $request->state['id'] == 4 ? 4 : 5;
        $user = User::find($request->id);
        $user->name = $request->name;
        $user->surnames = $request->surnames;
        $user->dob = $request->dob;
        $user->celphone = $request->celphone;
        $user->nick = $request->nick;
        $user->email = $request->email;
        $user->gender = $request->gender;
        $user->_rol = $request->rol['id'];
        $user->_state = $state;
        $user->_store = isset($request->store['value']) ? $request->store['value'] : $request->store['id'];
        $user->save();
        $res = $user->fresh()->toArray();
        if($res){
            //userapps
            $app = new UserApps();
            if($request->apps){
                $deleted = UserApps::where('_user',$request->id)->delete();
                $app->insert($request->apps);
            }
            //stores
            $stores = $request->use_store;
             foreach($stores as $store){
                $usestore = UserStores::where('_user',$request->id)->where('_store',$store['_store']);
                $usestore->update(['_state'=>$store['_state']]);
            }
            //permissions

            $useper = new UserModules;
            $delete = UserModules::where('_user',$request->id)->delete();
            $permissions = UserRol::with('permissions')->where('id',$request->rol['id'])->first();
            $permi = $permissions['permissions'];
            foreach($permi as $pre){
                $inserper[] = [
                    "_user"=>$request->id,
                    "_permission"=>$pre['_permission'],
                    "_module"=>$pre['_module']
                ];
            }
            $useper->insert($inserper);

            $inslog = new UserLog();
            $inslog->_user = $account;
            $inslog->_type_log = 3;
            $inslog->details = json_encode([
                "at"=>now()->format('Y-m-d H:m:s'),
                "device"=>$device,
                "account"=>["id"=>$request->id, "nick"=>$request->nick,"_state"=>$request->state['id']]
            ]);
            $inslog->save();
            return response()->json($res,200);
        }else{
            return response()->json('No se logro modificar el usuario ',500);
        }
    }

    public function getPosition(){
        $roles = Area::with('roles.permissions')->get();
        $modules = ModuleApp::with('children.children')->where('deep',0)->get();
        $permissions = Permission::all();

        $res = [
            "areas"=>$roles,
            "modules"=>$modules,
            "permissions"=>$permissions
        ];
        return response()->json($res,200);
    }

    public function addArea(Request $request){
        $area = Area::create($request->all());
        $res = $area->fresh(['roles'])->toArray();
        return response()->json($res,200);
    }

    public function addPuesto(Request $request){
        $area = UserRol::create($request->rol);
        $res = $area->fresh(['area'])->toArray();
        if($res){
            $permi = $request->permissions;
            // foreach($permi as $permis){
                $ins = $this->permissions($res['id'],$permi);
                $permisos = array_filter($ins, function ($val) {
                    return $val['_permission'] !== 0;
                });
                // $ins [] = [
                //     "_rol"=>$res['id'],
                //     "_permission"=>$permis['_permission'],
                //     "_module"=>$permis['id']
                // ];
            // }
            $roles  = RolDefaultPermission::insert($permisos);
            if($roles){
                return response()->json($res,200);
            }else{ return response()->json('Hubo un problema con los permissos');}
        }
    }

    public function getPermissionsRol(Request $request){
        $permissions = UserRol::with(['permissions'])->where('id',$request->id)->first();
        if($permissions){
            return response()->json($permissions,200);
        }else{
            return response()->json([],200);
        }

    }

    public function modifyPuesto(Request $request){
        $rol = UserRol::find($request->rol['id']);
        if ($rol) {
            $rol->name = $request->rol['name'];
            $rol->description = $request->rol['description'];
            $res = $rol->save();

            if ($res) {
                // Eliminar permisos existentes del rol
                RolDefaultPermission::where('_rol', $request->rol['id'])->delete();

                // Obtener nuevos permisos
                $ins = $this->permissions($request->rol['id'], $request->permissions);
                // Filtrar permisos que no sean 0
                $permisos = array_filter($ins, function ($val) {
                    return $val['_permission'] !== 0;
                });

                // Insertar nuevos permisos
                $roles = RolDefaultPermission::insert($permisos);

                if ($roles) {
                    // Obtener usuarios con el rol actualizado
                    $users = User::where('_rol', $request->rol['id'])->get();
                    if ($users) {
                        foreach ($users as $user) {
                            // Eliminar módulos de usuario existentes
                            UserModules::where('_user', $user->id)->delete();

                            // Crear nuevos módulos de usuario basados en los nuevos permisos
                            $userper = array_map(function ($val) use ($user) {
                                return [
                                    '_user' => $user->id,
                                    '_permission' => $val['_permission'],
                                    '_module' => $val['_module']
                                ];
                            }, $permisos);

                            // Insertar nuevos módulos de usuario
                            $insertar = UserModules::insert($userper);

                            if ($insertar) {
                                // Actualizar el estado del usuario
                                $modify = User::find($user->id);
                                $modify->_state = 5;
                                $modify->save();
                            }
                        }
                    }
                    return response()->json($roles, 200);
                } else {
                    return response()->json('Hubo un problema con los permisos', 500);
                }
            } else {
                return response()->json('Hubo un problema al actualizar el rol', 500);
            }
        } else {
            return response()->json('Rol no encontrado', 404);
        }
    }

    public function permissions($id, $permissions){
        static $ins = [];
        // $del = RolDefaultPermission::where('_rol',$id)->delete();
        foreach($permissions as $permis){
            $ins [] = [
                "_rol"=>$id,
                "_permission"=>$permis['_permission'],
                "_module"=>$permis['id']
            ];
            if(isset($permis['children']) && count($permis['children']) > 0){
                $this->permissions($id,$permis['children']);
            }
        }
        return $ins;
    }

    public function RessetPass($uid){
        $user =  User::find($uid); // Crear el usuario
        $user->password = Hash::make('12345');
        $user->change_password = 1;
        $user->_state = 5;
        $res = $user->save();
        if($res){
            return response()->json('La contrasena se reseteo correctamente',200);
        }else{
            return response()->json('No se pudo resetear la contrasena',500);
        }
    }

    public function InsertRCid(Request $request){
        $user = User::find($request->id);
        $user->RC_id = $request->RC_id;
        $res = $user->save();
        if($res){
            return response()->json('Se actualizo el id de el checador',200);
        }else{
            return response()->json('No se logro actualizar el id de el checador',500);
        }

    }

    public function getUserForStore(Request $request){
        $sid = $request->route('sid');
        $users = User::with('store:id,name','rol.area','state','useStore','apps')->where('_store',$sid)->get();
        $res = [
            "usuarios"=>$users
        ];
        return response()->json($res,200);
    }


}
