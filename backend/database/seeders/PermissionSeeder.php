<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [
            //suppliers
            ['name' => 'view dashboard'],

            //users
            ['name' => 'view users'],
            ['name' => 'create users'],
            ['name' => 'edit users'],
            ['name' => 'delete users'],

            //roles
            ['name' => 'view roles'],
            ['name' => 'create roles'],
            ['name' => 'edit roles'],
            ['name' => 'delete roles'],

            //suppliers
            ['name' => 'view suppliers'],
            ['name' => 'create suppliers'],
            ['name' => 'edit suppliers'],
            ['name' => 'delete suppliers'],

            //brands
            ['name' => 'view brands'],
            ['name' => 'create brands'],
            ['name' => 'edit brands'],
            ['name' => 'delete brands'],

            //models
            ['name' => 'view models'],
            ['name' => 'create models'],
            ['name' => 'edit models'],
            ['name' => 'delete models'],
            
            //invoices
            ['name' => 'view invoices'],
            ['name' => 'create invoices'],
            ['name' => 'edit invoices'],
            ['name' => 'delete invoices'],    

            //currencies
            ['name' => 'view currencies'],
            ['name' => 'create currencies'],
            ['name' => 'edit currencies'],
            ['name' => 'delete currencies'],

            //countries
            ['name' => 'view countries'],
            ['name' => 'create countries'],
            ['name' => 'edit countries'],
            ['name' => 'delete countries'],

            //plans
            ['name' => 'view plans'],
            ['name' => 'create plans'],
            ['name' => 'edit plans'],
            ['name' => 'delete plans'],
            
            //clients
            ['name' => 'view clients'],
            ['name' => 'create clients'],
            ['name' => 'edit clients'],
            ['name' => 'delete clients'],
            
            //billings
            ['name' => 'view billings'],
            ['name' => 'create billings'],
            ['name' => 'edit billings'],
            ['name' => 'delete billings'],

            //stock
            ['name' => 'view stock'],
            ['name' => 'create stock'],
            ['name' => 'edit stock'],
            ['name' => 'delete stock'],

            //sold
            ['name' => 'view sold'],
            ['name' => 'delete sold'],

            //agreements
            ['name' => 'view agreements'],
            ['name' => 'create agreements'],
            ['name' => 'edit agreements'],
            ['name' => 'delete agreements'],

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

            //notes
            ['name' => 'view notes'],
            ['name' => 'create notes'],
            ['name' => 'edit notes'],
            ['name' => 'delete notes'],

            //my team
            ['name' => 'view my-team'],
            ['name' => 'create my-team'],
            ['name' => 'edit my-team'],
            ['name' => 'delete my-team'],
            ['name' => 'view team-reports'],

            //settings
            ['name' => 'view company'],
            ['name' => 'view sms'],
            ['name' => 'view plan'],
        ]; 

        // Create or update permissions
        $this->createOrUpdatePermissions($permissions);

        // Assign permissions to roles
        $this->assignPermissionsToRoles();
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

    /**
     * Assign permissions to the corresponding roles
     *
     * @return void
     */
    private function assignPermissionsToRoles()
    {
        // Get all permissions
        $allPermissions = Permission::all();
        $allPermissionIds = $allPermissions->pluck('id')->toArray();

        // Assign all permissions to the SuperAdmin
        $superAdmin = Role::where('name', 'SuperAdmin')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions($allPermissionIds);
        }

        // Define specific permissions for the Administrator
        $adminPermissions = [
            'view users', 'create users', 'edit users', 'delete users', //users
            'view roles', 'create roles', 'edit roles', //roles
            'view company', 'view sms', //settings
            'view clients', 'create clients', 'edit clients', 'delete clients',//clients
            'view billings', 'create billings', 'edit billings', 'delete billings', //billing
            'view suppliers', 'create suppliers', 'edit suppliers', 'delete suppliers', //suppliers
            'view brands', 'create brands', 'edit brands', 'delete brands', //brands
            'view models', 'create models', 'edit models', 'delete models', //models
            'view stock', 'create stock', 'edit stock', 'delete stock', //stock
            'view sold', 'delete sold', //sold
            'view agreements', 'create agreements', 'edit agreements', 'delete agreements', //agreements
            'view signed-documents', 'create signed-documents', 'edit signed-documents', 'delete signed-documents', //signed-documents
            'view payouts', //payouts
            'view notes', 'create notes', 'edit notes', 'delete notes', //notes
            'view invoices', 'create invoices', 'edit invoices', 'delete invoices', //invoices
            'view currencies', 'create currencies', 'edit currencies', 'delete currencies', //currencies
            'view countries', 'create countries', 'edit countries', 'delete countries', //countries
            'view plans', 'create plans', 'edit plans', 'delete plans', //plans
        ];

        // Assign specific permissions to the Administrator
        $admin = Role::where('name', 'Administrator')->first();
        if ($admin) {
            $admin->syncPermissions($adminPermissions);
        }

        // Define specific permissions for the Supplier
        $supplierPermissions = [
            'view users', 'create users', 'edit users', 'delete users', //users
            'view my-team', 'create my-team', 'edit my-team', 'delete my-team', 'view team-reports', //my-teams
            'view company', 'view sms', 'view plan', //settings
            'view clients', 'create clients', 'edit clients', 'delete clients',//clients
            'view billings', 'create billings', 'edit billings', 'delete billings', //billing
            'view stock', 'create stock', 'edit stock', 'delete stock', //stock
            'view sold', 'delete sold', //sold
            'view agreements', 'create agreements', 'edit agreements', 'delete agreements', //agreements
            'view signed-documents', 'create signed-documents', 'edit signed-documents', 'delete signed-documents', //signed-documents
            'view payouts', 'create payouts', 'edit payouts', 'delete payouts', //payouts
            'view notes', 'create notes', 'edit notes', 'delete notes', //notes
        ];

        // Assign specific permissions to the Supplier
        $supplier = Role::where('name', 'Supplier')->first();
        if ($supplier) {
            $supplier->syncPermissions($supplierPermissions);
        }
    }
}