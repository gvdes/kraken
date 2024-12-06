<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ProductCategory;
use App\Models\Seasons;
use Illuminate\Support\Facades\DB;


class Seassons extends Seeder
{
    public function run()
    {

        $categories = [
            ["id"=>1,"name"=>"CALCULADORA","alias"=>'CAL',"deep"=>0,"root"=>0],
            ["id"=>2,"name"=>"ELECTRONICOS","alias"=>'ELE',"deep"=>0,"root"=>0],
            ["id"=>3,"name"=>"HOGAR","alias"=>'HOG',"deep"=>0,"root"=>0],
            ["id"=>4,"name"=>"JUGUETE","alias"=>'JUG',"deep"=>0,"root"=>0],
            ["id"=>5,"name"=>"MOCHILA","alias"=>'MOC',"deep"=>0,"root"=>0],
            ["id"=>6,"name"=>"NAVIDAD","alias"=>'NAV',"deep"=>0,"root"=>0],
            ["id"=>7,"name"=>"PAPELERIA","alias"=>'PAP',"deep"=>0,"root"=>0],
            ["id"=>8,"name"=>"PARAGUAS","alias"=>'PAR',"deep"=>0,"root"=>0],
            ["id"=>9,"name"=>"PELUCHES","alias"=>'PEL',"deep"=>0,"root"=>0],
            ["id"=>10,"name"=>"SISTEMAS","alias"=>'SIS',"deep"=>0,"root"=>0],
        ];

        DB::statement('SET SQL_SAFE_UPDATES= 0');
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        $ins = ProductCategory::insert($categories);

        DB::statement('SET SQL_SAFE_UPDATES= 1');
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');




        $seasson = [
        ["id"=>1,"name"=>"CALCULADORA","alias"=>'CAL',"_category"=>1],
        ["id"=>2,"name"=>"ELECTRONICOS","alias"=>'ELE',"_category"=>2],
        ["id"=>3,"name"=>"HOGAR","alias"=>'HOG',"_category"=>3],
        ["id"=>4,"name"=>"JUGUETE","alias"=>'JUG',"_category"=>4],
        ["id"=>5,"name"=>"MOCHILA","alias"=>'MOC',"_category"=>5],
        ["id"=>6,"name"=>"NAVIDAD","alias"=>'NAV',"_category"=>6],
        ["id"=>7,"name"=>"PAPELERIA","alias"=>'PAP',"_category"=>7],
        ["id"=>8,"name"=>"PARAGUAS","alias"=>'PAR',"_category"=>8],
        ["id"=>9,"name"=>"PELUCHES","alias"=>'PEL',"_category"=>9],
        ];
        $insseasson = Seasons::insert($seasson);



    }
}
