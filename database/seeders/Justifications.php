<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\JustificationType;
use App\Models\JustificationState;
use App\Models\PaymenPercentage;


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

        $states = [
            ['id'=> 1, "name"=>'Aprobado'],
            ['id'=> 2, "name"=>'En Espera'],
            ['id'=> 3, "name"=>'Desaprobado']
        ];
        $insstate = JustificationState::insert($states);

        $percentage = [
            ['id'=> 1, "percentage"=>0],
            ['id'=> 2, "percentage"=>20],
            ['id'=> 3, "percentage"=>50],
            ['id'=> 3, "percentage"=>100]
        ];
        $inspercentage = Per::PaymenPercentage($percentage);
    }
}
