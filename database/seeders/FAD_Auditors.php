<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FAD_Auditors extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /** ******************************************************
         * Setea los modulos que tendran por default los roles Auditores ( leads / operatives )
         * IMPORTANTE: si hay permisos adicionales a usuarios con este rol, estos se eliminaran
         * y deberan agregarse de forma manueal nuevamente
         * ******************************************************/

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        ### usuarios root y super admins
        ### Todos los modulos con el permiso 1, elimina todas las relaciones entre roots y permisos
        $leads = [
            [ "_rol"=>3, "_permission"=>2, "_module"=>"100.0" ], // Usuarios
            [ "_rol"=>3, "_permission"=>3, "_module"=>"102.0" ], // Almacenes
            [ "_rol"=>3, "_permission"=>1, "_module"=>"111.0" ], // Auditoria
        ];

        $operatives = [
            [ "_rol"=>4, "_permission"=>3, "_module"=>"100.0" ], // Usuarios
            [ "_rol"=>4, "_permission"=>3, "_module"=>"102.0" ], // Almacenes
            [ "_rol"=>4, "_permission"=>2, "_module"=>"111.0" ], // Auditoria
        ];

        echo "Eliminando permisos default a Aditores (3 y 4) ...\n"; sleep(1);
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
