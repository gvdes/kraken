<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
// use Illuminate\Support\Facades\DB;

class ResetModulesAuthRolesDefault extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            FillRoles::class,
            SetModulesApp::class,
            FAD_AdminsStores::class,
            FAD_Auditors::class,
            FAD_Storers::class,
            FAD_Salers::class,
        ]);
    }
}
