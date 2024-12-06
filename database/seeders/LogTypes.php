<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\LogType;

class LogTypes extends Seeder
{
    public function run()
    {

        $inststate = [
            ["id"=>1,"description"=>"Creacion de Cuenta","_module"=>'4f36'],
            ["id"=>2,"description"=>"Inicio de sesion","_module"=>'4f36'],
            ["id"=>3,"description"=>"Edicion de cuenta","_module"=>'4f36'],
            ["id"=>4,"description"=>"Creacion de store","_module"=>'bc02'],
            ["id"=>5,"description"=>"Edicion de store","_module"=>'bc02'],
            ["id"=>6,"description"=>"Creacion de pedido","_module"=>'4bed'],
            ["id"=>7,"description"=>"Proceso de pedido","_module"=>'4bed'],
            ["id"=>8,"description"=>"Termino de pedido","_module"=>'4bed'],
            ["id"=>9,"description"=>"Apertura de caja","_module"=>'a313'],
            ["id"=>10,"description"=>"Creacion de pedido","_module"=>'ade8'],
            ["id"=>11,"description"=>"Cambio de estado","_module"=>'ade8'],


        ];
        $state = LogType::insert($inststate);


    }
}
