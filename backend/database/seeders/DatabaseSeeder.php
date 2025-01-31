<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolSeeder::class,
            PermissionSeeder::class,
            AdminSeeder::class,

            StateSeeder::class,
            GenderSeeder::class,

            MorePermissionSeeder::class,
            SupplierSeeder::class,
            ClientSeeder::class
        ]);

    }
}
