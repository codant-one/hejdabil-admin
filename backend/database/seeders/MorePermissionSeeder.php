<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

use Spatie\Permission\Models\Permission;

class MorePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            //suppliers
            ['name' => 'view suppliers'],
            ['name' => 'create suppliers'],
            ['name' => 'edit suppliers'],
            ['name' => 'delete suppliers'],
            
            //clients
            ['name' => 'view clients'],
            ['name' => 'create clients'],
            ['name' => 'edit clients'],
            ['name' => 'delete clients'],
            
            //billing
            ['name' => 'view billing'],
            ['name' => 'create billing'],
            ['name' => 'edit billing'],
            ['name' => 'delete billing'],
            
            //invoices
            ['name' => 'view invoices'],
            ['name' => 'create invoices'],
            ['name' => 'edit invoices'],
            ['name' => 'delete invoices'],            

            //documents
            ['name' => 'view signed-documents'],
            ['name' => 'create signed-documents'],
            ['name' => 'edit signed-documents'],
            ['name' => 'delete signed-documents'],

            //payouts
            ['name' => 'view payouts'],
            ['name' => 'create payouts'],
            ['name' => 'edit payouts'],
            ['name' => 'delete payouts'],

        ]; 
        // Crear o actualizar permisos
        $this->createOrUpdatePermissions($permissions);

    }

    /**
     * Create or update permissions in the database
     *
     * @param array $permissions
     * @return void
     */
    private function createOrUpdatePermissions(array $permissions)
    {
        foreach ($permissions as $permissionData) {
            Permission::updateOrCreate(
                ['name' => $permissionData['name']], // Search by name
                [
                    'guard_name' => 'api',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]
            );
        }
    }
}
