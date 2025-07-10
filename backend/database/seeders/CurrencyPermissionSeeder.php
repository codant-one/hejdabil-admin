<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Spatie\Permission\Models\Permission;

class CurrencyPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::create(['name' => 'view currencies']);
        Permission::create(['name' => 'create currencies']);
        Permission::create(['name' => 'edit currencies']);
        Permission::create(['name' => 'delete currencies']);
    }
}
