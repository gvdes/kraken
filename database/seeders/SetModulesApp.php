<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SetModulesApp extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('modules_app')->truncate();

            /** *********************
             * Permisos de Sucursal :: CLUster
             * **********************/
            DB::table('modules_app')->insert([
                [ "id"=>"4f36", "name"=>"Usuarios", "root"=>"CLU", "deep"=>0, "path"=>"team", "details"=>json_encode([]) ],
                    [ "id"=>"972e", "name"=>"Builder", "root"=>"4f36", "deep"=>1, "path"=>"team/builder", "details"=>json_encode([]) ],

                [ "id"=>"bc02", "name"=>"Sucursales", "root"=>"CLU", "deep"=>0, "path"=>"stores", "details"=>json_encode([]) ],

                [ "id"=>"c48e", "name"=>"Productos", "root"=>"CLU", "deep"=>0, "path"=>"products", "details"=>json_encode([]) ],

                [ "id"=>"270b", "name"=>"Clientes", "root"=>"CLU", "deep"=>0, "path"=>"client", "details"=>json_encode([]) ],

                [ "id"=>"42c9", "name"=>"Proveedores", "root"=>"CLU", "deep"=>0, "path"=>"providers", "details"=>json_encode([]) ],

                [ "id"=>"9af8", "name"=>"Compras", "root"=>"CLU", "deep"=>0, "path"=>"buys", "details"=>json_encode([]) ],

                [ "id"=>"fe42", "name"=>"Checador", "root"=>"CLU", "deep"=>0, "path"=>"assists", "details"=>json_encode([]) ],

                [ "id"=>"419d", "name"=>"Monitor", "root"=>"CLU", "deep"=>0, "path"=>"monitor", "details"=>json_encode([]) ],

                [ "id"=>"8e26", "name"=>"Creditos", "root"=>"CLU", "deep"=>0, "path"=>"credits", "details"=>json_encode([]) ],
            ]);

            /** *********************
             * Permisos de Sucursal :: Branch OFfice
             * **********************/
            DB::table('modules_app')->insert([
                [ "id"=>"d5c2", "name"=>"Equipo", "root"=>"BOF", "deep"=>0, "path"=>"teamplate", "details"=>json_encode([]) ],

                [ "id"=>"9f4f", "name"=>"Ventas", "root"=>"BOF", "deep"=>0, "path"=>"preorders", "details"=>json_encode([]) ],

                [ "id"=>"4bed", "name"=>"Preventa", "root"=>"BOF", "deep"=>0, "path"=>"preorders", "details"=>json_encode([]) ],

                [ "id"=>"284c", "name"=>"Etiquetadora", "root"=>"BOF", "deep"=>0, "path"=>"labeler", "details"=>json_encode([]) ],

                [ "id"=>"b599", "name"=>"Almacenes", "root"=>"BOF", "deep"=>0, "path"=>"wareouses", "details"=>json_encode([]) ],
                    [ "id"=>"46d9", "name"=>"Producto", "root"=>"b599", "deep"=>1, "path"=>"wareouses/product", "details"=>json_encode([]) ],

                [ "id"=>"9a66", "name"=>"Ordenes", "root"=>"BOF", "deep"=>0, "path"=>"supply", "details"=>json_encode([]) ],

                [ "id"=>"ade8", "name"=>"Resurtido", "root"=>"BOF", "deep"=>0, "path"=>"restock", "details"=>json_encode([]) ],

                [ "id"=>"9ecc", "name"=>"Ubicador", "root"=>"BOF", "deep"=>0, "path"=>"locator", "details"=>json_encode([]) ],

            ]);

        /** ******************************************************
         * Setea los modulos que tendran por default los roles Root y Super Administradores
         * IMPORTANTE: si hay permisos adicionales ausuarios, estos se eliminaran
         * y deberan agregarse de forma manueal nuevamente
         * ******************************************************/

        ### usuarios root y super admins
        ### Todos los modulos con el permiso 1, elimina todas las relaciones entre roots y permisos
        echo "Eliminando permisos default a Root (1) y Super Administrador (2) ...\n"; sleep(1);
        $auths_dels = DB::table("role_default_permissions")->whereIn("_rol",[1,2])->delete();

        echo "Agregando...\n";
        $modules = DB::table("modules_app")->get();

        foreach($modules as $mod){
            $auths_root = [ "_rol"=>1, "_permission"=>1, "_module"=>$mod->id ]; // permiso para el los roots
            $auths_sadm = [ "_rol"=>2, "_permission"=>1, "_module"=>$mod->id ]; // permiso para los super admin
            $ins = DB::table("role_default_permissions")->insert($auths_root);
            echo "($mod->id) $mod->name :: Agregado a Root!\n";

            $ins = DB::table("role_default_permissions")->insert($auths_sadm);
            echo "($mod->id) $mod->name :: Agregado a Super Admin!\n";
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        echo "Tabla modules_app truncada y se volvieron a insertar los modulos!!";
    }
}
