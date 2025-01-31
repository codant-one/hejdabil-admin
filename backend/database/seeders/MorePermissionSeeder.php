<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Spatie\Permission\Models\Permission;

class MorePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::create(['name' => 'view suppliers']);
        Permission::create(['name' => 'create suppliers']);
        Permission::create(['name' => 'edit suppliers']);
        Permission::create(['name' => 'delete suppliers']);

        Permission::create(['name' => 'view clients']);
        Permission::create(['name' => 'create clients']);
        Permission::create(['name' => 'edit clients']);
        Permission::create(['name' => 'delete clients']);

        Permission::create(['name' => 'view billing']);
        Permission::create(['name' => 'create billing']);
        Permission::create(['name' => 'edit billing']);
        Permission::create(['name' => 'delete billing']);

        Permission::create(['name' => 'view invoices']);
        Permission::create(['name' => 'create invoices']);
        Permission::create(['name' => 'edit invoices']);
        Permission::create(['name' => 'delete invoices']);

    }
}
