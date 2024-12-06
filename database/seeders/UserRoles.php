<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\UserRol;


class UserRoles extends Seeder
{
    public function run()
    {
        $area = new UserRol();
        $area->name = 'Root';
        $area->description = 'Root';
        $area->type_rol = 0;
        $area->hierarchy = 0;
        $area->_area = 1;
        $area->save();
    }
}
