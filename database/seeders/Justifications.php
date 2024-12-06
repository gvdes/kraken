<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\JustificationType;

class Justifications extends Seeder
{
    public function run()
    {
        $insresponsibles = [
            ["id"=>1,"name"=>"Escuela"],
            ["id"=>2,"name"=>"Incapacidad"],
            ["id"=>3,"name"=>"Regreso/Falta"],
            ["id"=>4,"name"=>"P S/G"],
            ["id"=>5,"name"=>"P C/G"],
            ["id"=>6,"name"=>"Paternidad"],
            ["id"=>7,"name"=>"Ingreso"],
            ["id"=>8,"name"=>"Suspencion"],
            ["id"=>9,"name"=>"China"],
            ["id"=>10,"name"=>"Descanso"],
            ["id"=>11,"name"=>"No Pasa Huella"],
            ["id"=>12,"name"=>"R.Falla Transporte"],
            ["id"=>13,"name"=>"Fallecimiento Familiar"],
            ["id"=>14,"name"=>"Descarga"],
            ["id"=>15,"name"=>"Retardo"],
            ["id"=>16,"name"=>"Vacaciones"],

        ];
        $insresponsibles = JustificationType::insert($insresponsibles);
    }
}
