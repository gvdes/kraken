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
            [ "_rol"=>9, "_permission"=>1, "_module"=>"284c" ],//etiquetadora
            [ "_rol"=>9, "_permission"=>2, "_module"=>"4a82" ],//presupuestos

            [ "_rol"=>9, "_permission"=>2, "_module"=>"4bed" ],//preventa
            [ "_rol"=>9, "_permission"=>2, "_module"=>"9b4e" ],//checkin
            [ "_rol"=>9, "_permission"=>1, "_module"=>"9ecc" ],//ubicador
            [ "_rol"=>9, "_permission"=>2, "_module"=>"a313" ],//cajas
            [ "_rol"=>9, "_permission"=>3, "_module"=>"b599" ],//almacenes
            [ "_rol"=>9, "_permission"=>2, "_module"=>"9a66" ],//preventa en almasens
            [ "_rol"=>9, "_permission"=>1, "_module"=>"ade8" ],//resurtid
            [ "_rol"=>9, "_permission"=>1, "_module"=>"4e43" ],//reporteria
            [ "_rol"=>9, "_permission"=>1, "_module"=>"a831" ],//configuarcion preventa

        ];

        ### modulos subgerentes (10)
        $auxiliars = [
            [ "_rol"=>10, "_permission"=>2, "_module"=>"284c" ],//etiquetadora
            [ "_rol"=>10, "_permission"=>3, "_module"=>"4a82" ],//presupuestos

            [ "_rol"=>10, "_permission"=>2, "_module"=>"4bed" ],//preventa
            [ "_rol"=>10, "_permission"=>2, "_module"=>"9b4e" ],//checkin
            [ "_rol"=>10, "_permission"=>2, "_module"=>"9ecc" ],//ubicador
            [ "_rol"=>10, "_permission"=>2, "_module"=>"a313" ],//cajas
            [ "_rol"=>10, "_permission"=>3, "_module"=>"b599" ],//almacenes
            [ "_rol"=>10, "_permission"=>2, "_module"=>"9a66" ],//preventa en almasens
            [ "_rol"=>10, "_permission"=>2, "_module"=>"ade8" ],//resurtid
            [ "_rol"=>10, "_permission"=>2, "_module"=>"4e43" ],//reporteria
            [ "_rol"=>10, "_permission"=>2, "_module"=>"a831" ],//configuarcion preventa
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
