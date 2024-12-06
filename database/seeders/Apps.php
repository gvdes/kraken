<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Apps as App;



class Apps extends Seeder
{

    public function run()
    {

        $inststate = [
            ["id"=>1,"name"=>"Etiquetadora","alias"=>'ETQ',"icon"=>null,"path"=>'/apps/labeling'],
            ["id"=>2,"name"=>"Ubicador","alias"=>'UBC',"icon"=>null,"path"=>'/apps/locator'],
            ["id"=>3,"name"=>"Monitor","alias"=>'MON',"icon"=>null,"path"=>'/apps/monitor'],
            ["id"=>4,"name"=>"Reporteria","alias"=>'REP',"icon"=>null,"path"=>'/apps/reports'],
            ["id"=>5,"name"=>"CheckIn","alias"=>'CHK',"icon"=>null,"path"=>'/apps/checkin'],
            ["id"=>6,"name"=>"Traspasos","alias"=>'TRS',"icon"=>null,"path"=>'/apps/trasnfers'],
        ];
        $state = App::insert($inststate);


    }
}
