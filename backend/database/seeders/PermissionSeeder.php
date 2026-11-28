<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

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
            ['name' => 'view dashboard', 'label' => 'Visa'],

            //users
            ['name' => 'view users', 'label' => 'Visa'],
            ['name' => 'create users', 'label' => 'Skapa'],
            ['name' => 'edit users', 'label' => 'Redigera'],
            ['name' => 'delete users', 'label' => 'Ta bort'],

            //roles
            ['name' => 'view roles', 'label' => 'Visa'],
            ['name' => 'create roles', 'label' => 'Skapa'],
            ['name' => 'edit roles', 'label' => 'Redigera'],
            ['name' => 'delete roles', 'label' => 'Ta bort'],

            //suppliers
            ['name' => 'view suppliers', 'label' => 'Visa'],
            ['name' => 'create suppliers', 'label' => 'Skapa'],
            ['name' => 'edit suppliers', 'label' => 'Redigera'],
            ['name' => 'delete suppliers', 'label' => 'Ta bort'],

            //brands
            ['name' => 'view brands', 'label' => 'Visa'],
            ['name' => 'create brands', 'label' => 'Skapa'],
            ['name' => 'edit brands', 'label' => 'Redigera'],
            ['name' => 'delete brands', 'label' => 'Ta bort'],

            //models
            ['name' => 'view models', 'label' => 'Visa'],
            ['name' => 'create models', 'label' => 'Skapa'],
            ['name' => 'edit models', 'label' => 'Redigera'],
            ['name' => 'delete models', 'label' => 'Ta bort'],
            
            //invoices
            ['name' => 'view invoices', 'label' => 'Visa'],
            ['name' => 'create invoices', 'label' => 'Skapa'],
            ['name' => 'edit invoices', 'label' => 'Redigera'],
            ['name' => 'delete invoices', 'label' => 'Ta bort'],    

            //currencies
            ['name' => 'view currencies', 'label' => 'Visa'],
            ['name' => 'create currencies', 'label' => 'Skapa'],
            ['name' => 'edit currencies', 'label' => 'Redigera'],
            ['name' => 'delete currencies', 'label' => 'Ta bort'],

            //countries
            ['name' => 'view countries', 'label' => 'Visa'],
            ['name' => 'create countries', 'label' => 'Skapa'],
            ['name' => 'edit countries', 'label' => 'Redigera'],
            ['name' => 'delete countries', 'label' => 'Ta bort'],

            //plans
            ['name' => 'view plans', 'label' => 'Visa'],
            ['name' => 'create plans', 'label' => 'Skapa'],
            ['name' => 'edit plans', 'label' => 'Redigera'],
            ['name' => 'delete plans', 'label' => 'Ta bort'],

            //notifications
            ['name' => 'view notifications', 'label' => 'Visa'],
            ['name' => 'create notifications', 'label' => 'Skapa'],
            ['name' => 'delete notifications', 'label' => 'Ta bort'],
            
            //clients
            ['name' => 'view clients', 'label' => 'Visa'],
            ['name' => 'create clients', 'label' => 'Skapa'],
            ['name' => 'edit clients', 'label' => 'Redigera'],
            ['name' => 'delete clients', 'label' => 'Ta bort'],
            
            //billings
            ['name' => 'view billings', 'label' => 'Visa'],
            ['name' => 'create billings', 'label' => 'Skapa'],
            ['name' => 'edit billings', 'label' => 'Redigera'],
            ['name' => 'delete billings', 'label' => 'Ta bort'],

            //stock
            ['name' => 'view stock', 'label' => 'Visa'],
            ['name' => 'create stock', 'label' => 'Skapa'],
            ['name' => 'edit stock', 'label' => 'Redigera'],
            ['name' => 'delete stock', 'label' => 'Ta bort'],

            //sold
            ['name' => 'view sold', 'label' => 'Visa'],
            ['name' => 'delete sold', 'label' => 'Ta bort'],

            //agreements
            ['name' => 'view agreements', 'label' => 'Visa'],
            ['name' => 'create agreements', 'label' => 'Skapa'],
            ['name' => 'edit agreements', 'label' => 'Redigera'],
            ['name' => 'delete agreements', 'label' => 'Ta bort'],

            //documents
            ['name' => 'view signed-documents', 'label' => 'Visa'],
            ['name' => 'create signed-documents', 'label' => 'Skapa'],
            ['name' => 'edit signed-documents', 'label' => 'Redigera'],
            ['name' => 'delete signed-documents', 'label' => 'Ta bort'],

            //payouts
            ['name' => 'view payouts', 'label' => 'Visa'],
            ['name' => 'create payouts', 'label' => 'Skapa'],
            ['name' => 'edit payouts', 'label' => 'Redigera'],
            ['name' => 'delete payouts', 'label' => 'Ta bort'],

            //notes
            ['name' => 'view notes', 'label' => 'Visa'],
            ['name' => 'create notes', 'label' => 'Skapa'],
            ['name' => 'edit notes', 'label' => 'Redigera'],
            ['name' => 'delete notes', 'label' => 'Ta bort'],

            //my team
            ['name' => 'view my-team', 'label' => 'Visa'],
            ['name' => 'create my-team', 'label' => 'Skapa'],
            ['name' => 'edit my-team', 'label' => 'Redigera'],
            ['name' => 'delete my-team', 'label' => 'Ta bort'],
            ['name' => 'view team-reports', 'label' => 'Visa Teamrapporter'],

            //settings
            ['name' => 'view company', 'label' => 'Visa Företag'],
            ['name' => 'view sms', 'label' => 'Visa SMS'],
            ['name' => 'view plan', 'label' => 'Visa Plan'],
            ['name' => 'view trash', 'label' => 'Visa Papperskorg'],

            //help
            ['name' => 'view help-categories', 'label' => 'Visa'],
            ['name' => 'create help-categories', 'label' => 'Skapa'],
            ['name' => 'edit help-categories', 'label' => 'Redigera'],
            ['name' => 'delete help-categories', 'label' => 'Ta bort'],
            ['name' => 'view help-videos', 'label' => 'Visa'],
            ['name' => 'create help-videos', 'label' => 'Skapa'],
            ['name' => 'edit help-videos', 'label' => 'Redigera'],
            ['name' => 'delete help-videos', 'label' => 'Ta bort'],
            ['name' => 'view help-center', 'label' => 'Visa'],
        ]; 

        // Skapa or update permissions
        $this->createOrUpdatePermissions($permissions);

        // Assign permissions to roles
        $this->assignPermissionsToRoles();

        // Clear Spatie's permission cache so the new assignments take effect immediately
        app(PermissionRegistrar::class)->forgetCachedPermissions();
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
                    'label' => $permissionData['label'],
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
            'view company', 'view sms', 'view trash', //settings
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
            'view notifications', 'create notifications', 'delete notifications', //notifications
            'view help-categories', 'create help-categories', 'edit help-categories', 'delete help-categories', //help-categories
            'view help-videos', 'create help-videos', 'edit help-videos', 'delete help-videos', //help-videos
            'view help-center', //help-center
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
            'view company', 'view sms', 'view plan', 'view trash', //settings
            'view clients', 'create clients', 'edit clients', 'delete clients',//clients
            'view billings', 'create billings', 'edit billings', 'delete billings', //billing
            'view stock', 'create stock', 'edit stock', 'delete stock', //stock
            'view sold', 'delete sold', //sold
            'view agreements', 'create agreements', 'edit agreements', 'delete agreements', //agreements
            'view signed-documents', 'create signed-documents', 'edit signed-documents', 'delete signed-documents', //signed-documents
            'view payouts', 'create payouts', 'edit payouts', 'delete payouts', //payouts
            'view notes', 'create notes', 'edit notes', 'delete notes', //notes
            'view help-center', //help-center
        ];

        // Assign specific permissions to the Supplier
        $supplier = Role::where('name', 'Supplier')->first();
        if ($supplier) {
            $supplier->syncPermissions($supplierPermissions);
        }

        // Define specific permissions for the User
        $userPermissions = [
            'view company', 'view sms', 'view plan', 'view trash', //settings
            'view help-center', //help-center
        ];

        // Assign specific permissions to the User
        $user = Role::where('name', 'User')->first();
        if ($user) {
            $user->syncPermissions($userPermissions);
        }
    }
}