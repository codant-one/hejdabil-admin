<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Spatie\Permission\Models\Permission;

class InventoryPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::create(['name' => 'view brands']);
        Permission::create(['name' => 'create brands']);
        Permission::create(['name' => 'edit brands']);
        Permission::create(['name' => 'delete brands']);

        Permission::create(['name' => 'view models']);
        Permission::create(['name' => 'create models']);
        Permission::create(['name' => 'edit models']);
        Permission::create(['name' => 'delete models']);

        Permission::create(['name' => 'view stock']);
        Permission::create(['name' => 'create stock']);
        Permission::create(['name' => 'edit stock']);
        Permission::create(['name' => 'delete stock']);

        Permission::create(['name' => 'view sold']);
        Permission::create(['name' => 'delete sold']);

        Permission::create(['name' => 'view notes']);
        Permission::create(['name' => 'create notes']);
        Permission::create(['name' => 'edit notes']);
        Permission::create(['name' => 'delete notes']);

        Permission::create(['name' => 'view agreements']);
        Permission::create(['name' => 'create agreements']);
        Permission::create(['name' => 'edit agreements']);
        Permission::create(['name' => 'delete agreements']);

    }
}
