<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FAD_Storers extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /** ******************************************************
         * Setea los modulos que tendran por default los roles Almacenistas ( leads / operatives )
         * IMPORTANTE: si hay permisos adicionales a usuarios con este rol, estos se eliminaran
         * y deberan agregarse manualmente nuevamente
         * ******************************************************/

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        ### modulos Almacenistas leads (11)
        $leads = [
            [ "_rol"=>11, "_permission"=>1, "_module"=>"2.0" ],
            [ "_rol"=>11, "_permission"=>1, "_module"=>"2.1" ],
            [ "_rol"=>11, "_permission"=>1, "_module"=>"2.2" ],
            [ "_rol"=>11, "_permission"=>1, "_module"=>"2.3" ],
            [ "_rol"=>11, "_permission"=>1, "_module"=>"2.4" ],
            [ "_rol"=>11, "_permission"=>1, "_module"=>"2.5" ],
            [ "_rol"=>11, "_permission"=>1, "_module"=>"2.6" ],
            [ "_rol"=>11, "_permission"=>1, "_module"=>"2.7" ],
            [ "_rol"=>11, "_permission"=>1, "_module"=>"2.8" ],
        ];

        ### modulos Almacenistas leads (12)
        $operatives = [
            [ "_rol"=>12, "_permission"=>1, "_module"=>"2.0" ],
            [ "_rol"=>12, "_permission"=>1, "_module"=>"2.1" ],
            [ "_rol"=>12, "_permission"=>1, "_module"=>"2.2" ],
            [ "_rol"=>12, "_permission"=>1, "_module"=>"2.3" ],
            [ "_rol"=>12, "_permission"=>1, "_module"=>"2.4" ],
            [ "_rol"=>12, "_permission"=>1, "_module"=>"2.5" ],
            [ "_rol"=>12, "_permission"=>1, "_module"=>"2.6" ],
            [ "_rol"=>12, "_permission"=>1, "_module"=>"2.7" ],
            [ "_rol"=>12, "_permission"=>1, "_module"=>"2.8" ],
        ];

        echo "Eliminando permisos default a Almacenistas (11 y 12) ...\n"; sleep(1);
        $auths_dels = DB::table("role_default_permissions")->whereIn("_rol",[11,12])->delete();

        foreach($leads as $mod){
            $ins = DB::table("role_default_permissions")->insert($mod);
            echo "MOD: ".$mod["_module"]." <==> AUTH: ".$mod["_permission"]." agregado a Almacenista (lead)\n";
        }

        foreach($operatives as $mod){
            $ins = DB::table("role_default_permissions")->insert($mod);
            echo "MOD: ".$mod["_module"]." <==> AUTH: ".$mod["_permission"]." agregado a Almacenista (op)\n";
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
