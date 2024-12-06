<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Apps;
use App\Models\UserModules;
use App\Models\UserRol;

use App\Models\UserLog;
use App\Models\UserApps;
use App\Models\UserStores;

use App\Models\UserStates;
use Illuminate\Support\Facades\Hash;

class RootUserSeeder extends Seeder
{
    public function run()
    {
        $inststate = [
            ["id"=>1,"name"=>"Nuevo"],
            ["id"=>2,"name"=>"Activo"],
            ["id"=>3,"name"=>"Bloqueado"],
            ["id"=>4,"name"=>"Archivado"],
            ["id"=>5,"name"=>"Reinicio"],

        ];
        $state = UserStates::insert($inststate);



        $user = new User();
        $user->name = 'Jefferson';
        $user->surnames = 'Gutierritos';
        $user->dob = now();
        $user->celphone = '5591620437';
        $user->nick = 'root';
        $user->password = Hash::make('12345');
        $user->change_password = 1;
        $user->email = 'desarrollo@grupovizcarra.mx';
        $user->gender = 'I';
        $user->_rol = 1;
        $user->_state = 1;
        $user->_store = 1;
        $user->save();

        $res = $user->fresh()->toArray();
            // $user->avatar = 'heroedeadpool.png';
            // $user->save();

            // $avatar = $request->file('avatar');
            $uid = $res['id'];
            $folderPath = public_path('multimedia/profiles/'.$uid.'/');
            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0777, true);
            }

            $currentAvatarPath = public_path('multimedia/' .'heroedeadpool.png');

            $newAvatarPath = $folderPath . 'heroedeadpool.png';

            // Verifica si el archivo existe en la ubicación actual
            if (file_exists($currentAvatarPath)) {
                // Mueve el archivo a la nueva ubicación
                rename($currentAvatarPath, $newAvatarPath);
            }
            // $avatarPath = $folderPath . '/' . $avatar->getClientOriginalName();
            // $avatar->move($folderPath, $avatar->getClientOriginalName());
            // $user->avatar = $avatar->getClientOriginalName();
            $user->avatar = 'heroedeadpool.png';
            $user->save();

            $apps = Apps::all();
            $userApp = new UserApps();
            foreach($apps as $app){
                $insapp[] = [
                    "_user"=> $res['id'],
                    "_app"=>$app->id,
                ];
             }
            $userApp->insert($insapp);

            $stores = UserStores::where('_user',$res['id']);
            $stores->update(['_state'=>1]);

            $useper = new UserModules;
            $permissions = UserRol::with('permissions')->where('id',1)->first();
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
            $inslog->_user = 1;
            $inslog->_type_log = 1;
            $inslog->details = json_encode([
                "at"=>now()->format('Y-m-d H:m:s'),
                "device"=>'localhost',
                "account"=>["id"=>$res['id'], "nick"=>$res['nick']]
            ]);
            $inslog->save();
    }
}
