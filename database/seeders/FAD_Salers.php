<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FAD_Salers extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /** ******************************************************
         * Setea los modulos que tendran por default los roles Vendedores ( leads / operatives )
         * IMPORTANTE: si hay permisos adicionales a usuarios con este rol, estos se eliminaran
         * y deberan agregarse de forma manueal nuevamente
         * ******************************************************/

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        ### usuarios Vendedores ( leads / operatives )
        $leads = [
            [ "_rol"=>15, "_permission"=>1, "_module"=>"3.0" ],
            [ "_rol"=>15, "_permission"=>1, "_module"=>"3.1" ],
            [ "_rol"=>15, "_permission"=>1, "_module"=>"3.1.1" ],
            [ "_rol"=>15, "_permission"=>1, "_module"=>"3.2" ],
            [ "_rol"=>15, "_permission"=>1, "_module"=>"3.3" ],
            [ "_rol"=>15, "_permission"=>1, "_module"=>"3.4" ],
            [ "_rol"=>15, "_permission"=>2, "_module"=>"3.5" ],
            [ "_rol"=>15, "_permission"=>1, "_module"=>"3.6" ],
        ];

        $operatives = [
            [ "_rol"=>16, "_permission"=>3, "_module"=>"3.0" ],
            [ "_rol"=>16, "_permission"=>2, "_module"=>"3.1" ],
            [ "_rol"=>16, "_permission"=>2, "_module"=>"3.2" ],
        ];

        echo "Eliminando permisos default a Ventas (15 y 16) ...\n"; sleep(1);
        $auths_dels = DB::table("role_default_permissions")->whereIn("_rol",[3,4])->delete();

        foreach($leads as $mod){
            $ins = DB::table("role_default_permissions")->insert($mod);
            echo "MOD: ".$mod["_module"]." <==> AUTH: ".$mod["_permission"]." agregado a Auditor (lead)\n";
        }

        foreach($operatives as $mod){
            $ins = DB::table("role_default_permissions")->insert($mod);
            echo "MOD: ".$mod["_module"]." <==> AUTH: ".$mod["_permission"]." agregado a Auditor (op)\n";
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
