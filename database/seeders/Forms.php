<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\FormResponsible;
use App\Models\FormType;
use App\Models\QuestionType;




class Forms extends Seeder
{

    public function run()
    {
        $instypes = [
            ["id"=>1,"name"=>"Sucursal"],
            ["id"=>2,"name"=>"Cedis"],
            ["id"=>3,"name"=>"Grupal"],
        ];
        $types = FormType::insert($instypes);

        $insresponsibles = [
            ["id"=>1,"name"=>"Todos"],
            ["id"=>2,"name"=>"Auditoria"],
            ["id"=>3,"name"=>"Sucursales"],
        ];
        $insresponsibles = FormResponsible::insert($insresponsibles);

        $instypesques = [
            ["id"=>1,"name"=>"Texto"],
            ["id"=>2,"name"=>"Opciones"],
            ["id"=>3,"name"=>"Archivo"],
            ["id"=>4,"name"=>"Colaboradores"],

        ];
        $typesquest = QuestionType::insert($instypesques);

    }
}
