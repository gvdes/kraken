<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Area;



class UserArea extends Seeder
{
    public function run()
    {
        $area = new Area();
        $area->name = 'Desarrollo';
        $area->save();
    }
}
