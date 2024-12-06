<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ModuleApp;
use App\Models\Permission;
use App\Models\RolDefaultPermission;
use App\Models\Rol;

class RolDefaultPermissions extends Seeder
{
    public function run()
    {
        $modules = ModuleApp::all();
        $default = new RolDefaultPermission();
        $ins = [];
        foreach($modules as $module){
            $ins[] = [
                "_rol"=>1,
                "_permission"=>1,
                "_module"=>$module->id
            ];
        }
        $res = $default->insert($ins);
        echo $res;
    }
}
