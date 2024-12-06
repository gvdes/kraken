<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;

class Permissions extends Seeder
{
    public function run()
    {
        $permissions = new Permission();
        $insert = [
            ['id'=>0,'name'=>'Sin Permiso'],
            ['id'=>1,'name'=>'Control Total'],
            ['id'=>2,'name'=>'Crear y editar'],
            ['id'=>3,'name'=>'Vista']
        ];

        $permissions->insert($insert);
    }
}
