<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            UserArea::class,
            UserRoles::class,
            Permissions::class,
            Modules::class,
            RolDefaultPermissions::class,
            Seassons::class,
            Stores::class,
            Apps::class,
            LogTypes::class,
            RootUserSeeder::class,
            Forms::class,
            Justifications::class
        ]);
    }
}
