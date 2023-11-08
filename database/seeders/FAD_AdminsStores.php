<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FAD_AdminsStores extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /** ******************************************************
         * Setea los modulos que tendran por default los roles Gerentes y Auxuliares de gerentes
         * IMPORTANTE: si hay permisos adicionales a usuarios con este rol, estos se eliminaran
         * y deberan agregarse de forma manual nuevamente
         * ******************************************************/

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        ### modulos Gerentes (9)
        $leads = [
            [ "_rol"=>9, "_permission"=>1, "_module"=>"1.0" ],
            [ "_rol"=>9, "_permission"=>2, "_module"=>"1.1" ],

            [ "_rol"=>9, "_permission"=>1, "_module"=>"2.0" ],
            [ "_rol"=>9, "_permission"=>1, "_module"=>"2.1" ],
            [ "_rol"=>9, "_permission"=>1, "_module"=>"2.2" ],
            [ "_rol"=>9, "_permission"=>1, "_module"=>"2.3" ],
            [ "_rol"=>9, "_permission"=>1, "_module"=>"2.4" ],
            [ "_rol"=>9, "_permission"=>1, "_module"=>"2.5" ],
            [ "_rol"=>9, "_permission"=>1, "_module"=>"2.6" ],
            [ "_rol"=>9, "_permission"=>1, "_module"=>"2.7" ],
            [ "_rol"=>9, "_permission"=>1, "_module"=>"2.8" ],

            [ "_rol"=>9, "_permission"=>1, "_module"=>"3.0" ],
            [ "_rol"=>9, "_permission"=>1, "_module"=>"3.1" ],
            [ "_rol"=>9, "_permission"=>1, "_module"=>"3.1.1" ],
            [ "_rol"=>9, "_permission"=>1, "_module"=>"3.2" ],
            [ "_rol"=>9, "_permission"=>1, "_module"=>"3.3" ],
            [ "_rol"=>9, "_permission"=>1, "_module"=>"3.4" ],
            [ "_rol"=>9, "_permission"=>1, "_module"=>"3.5" ],
            [ "_rol"=>9, "_permission"=>1, "_module"=>"3.6" ],

            [ "_rol"=>9, "_permission"=>1, "_module"=>"4.0" ],
            [ "_rol"=>9, "_permission"=>1, "_module"=>"5.0" ],
        ];

        ### modulos subgerentes (10)
        $auxiliars = [
            [ "_rol"=>10, "_permission"=>3, "_module"=>"1.0" ],

            [ "_rol"=>10, "_permission"=>3, "_module"=>"2.0" ],
            [ "_rol"=>10, "_permission"=>3, "_module"=>"2.1" ],
            [ "_rol"=>10, "_permission"=>3, "_module"=>"2.2" ],
            [ "_rol"=>10, "_permission"=>2, "_module"=>"2.3" ],
            [ "_rol"=>10, "_permission"=>3, "_module"=>"2.4" ],
            [ "_rol"=>10, "_permission"=>3, "_module"=>"2.5" ],
            [ "_rol"=>10, "_permission"=>2, "_module"=>"2.6" ],
            [ "_rol"=>10, "_permission"=>2, "_module"=>"2.7" ],
            [ "_rol"=>10, "_permission"=>3, "_module"=>"2.8" ],

            [ "_rol"=>9, "_permission"=>3, "_module"=>"3.0" ],
            [ "_rol"=>9, "_permission"=>2, "_module"=>"3.1" ],
            [ "_rol"=>9, "_permission"=>2, "_module"=>"3.1.1" ],
            [ "_rol"=>9, "_permission"=>1, "_module"=>"3.2" ],
            [ "_rol"=>9, "_permission"=>1, "_module"=>"3.3" ],
            [ "_rol"=>9, "_permission"=>2, "_module"=>"3.4" ],
            [ "_rol"=>9, "_permission"=>2, "_module"=>"3.5" ],
            [ "_rol"=>9, "_permission"=>2, "_module"=>"3.6" ],

            [ "_rol"=>10, "_permission"=>1, "_module"=>"4.0" ],
            [ "_rol"=>10, "_permission"=>3, "_module"=>"5.0" ],
        ];

        echo "Eliminando permisos default a Gerentes y Auxiliares (9 y 10) ...\n"; sleep(1);
        $auths_dels = DB::table("role_default_permissions")->whereIn("_rol",[9,10])->delete();

        foreach($leads as $mod){
            $ins = DB::table("role_default_permissions")->insert($mod);
            echo "MOD: ".$mod["_module"]." <==> AUTH: ".$mod["_permission"]." agregado a Gerente de Sucursal\n";
        }

        foreach($auxiliars as $mod){
            $ins = DB::table("role_default_permissions")->insert($mod);
            echo "MOD: ".$mod["_module"]." <==> AUTH: ".$mod["_permission"]." agregado a Subgerente\n";
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
