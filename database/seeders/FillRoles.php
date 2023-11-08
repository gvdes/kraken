<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FillRoles extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('user_roles')->truncate();

            DB::table('user_roles')->insert([
                [ "id"=>1, "name"=>"Root", "description"=>"", "type_rol"=>0, "hierarchy"=>0 ],
                [ "id"=>2, "name"=>"Super Admin", "description"=>"", "type_rol"=>1, "hierarchy"=>1 ],
                [ "id"=>3, "name"=>"Auditor (lead)", "description"=>"", "type_rol"=>1, "hierarchy"=>2 ],
                [ "id"=>4, "name"=>"Auditor", "description"=>"", "type_rol"=>1, "hierarchy"=>3 ],
                [ "id"=>5, "name"=>"Compras (lead)", "description"=>"", "type_rol"=>1, "hierarchy"=>2 ],
                [ "id"=>6, "name"=>"Compras", "description"=>"", "type_rol"=>1, "hierarchy"=>3 ],
                [ "id"=>7, "name"=>"RRHH (lead)", "description"=>"", "type_rol"=>1, "hierarchy"=>2 ],
                [ "id"=>8, "name"=>"RRHH", "description"=>"", "type_rol"=>1, "hierarchy"=>3 ],

                [ "id"=>9, "name"=>"Gerente de Sucursal", "description"=>"", "type_rol"=>2, "hierarchy"=>2 ],
                [ "id"=>10, "name"=>"Subgerente", "description"=>"", "type_rol"=>2, "hierarchy"=>3 ],
                [ "id"=>11, "name"=>"Almacenista (lead)", "description"=>"", "type_rol"=>2, "hierarchy"=>4 ],
                [ "id"=>12, "name"=>"Almacenista", "description"=>"", "type_rol"=>2, "hierarchy"=>5 ],
                [ "id"=>13, "name"=>"Cajero (lead)", "description"=>"", "type_rol"=>2, "hierarchy"=>4 ],
                [ "id"=>14, "name"=>"Cajero", "description"=>"", "type_rol"=>2, "hierarchy"=>5 ],
                [ "id"=>15, "name"=>"Vendedor (lead)", "description"=>"", "type_rol"=>2, "hierarchy"=>4 ],
                [ "id"=>16, "name"=>"Vendedor", "description"=>"", "type_rol"=>2, "hierarchy"=>5 ],
            ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        echo "Roles creados!! \n";
    }
}
