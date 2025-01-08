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
        // Asigne all permissions to ROL SuperAdmin
        $admin = Role::where('name', 'SuperAdmin')->first();

        Permission::create([
            'name' => 'administrator'
        ])->assignRole($admin);
        
        $data = [ 
            ['name' => 'view dashboard'],
            ['name' => 'view users'],
            ['name' => 'create users'],
            ['name' => 'edit users'],
            ['name' => 'delete users'],
            ['name' => 'view roles'],
            ['name' => 'create roles'],
            ['name' => 'edit roles'],
            ['name' => 'delete roles'],
        ]; 

        $permissions = [];

        foreach($data as $permission){
            $permission['guard_name'] = 'api';
            $permission['created_at'] = Carbon::now();
            $permission['updated_at'] = Carbon::now();
            $permissions[] = $permission;
        }

        $data = Permission::insert($permissions);

        $dashboard = [ 
            'view dashboard'         
        ];

        $users = [ 
            'view users',
            'create users',
            'edit users'           
        ];

        $roles = [
            'view roles',
            'create roles',
            'edit roles'            
        ];
        
        $admin = Role::where('name', 'Administrator')->first();

        $admin->givePermissionTo(
            array_merge($dashboard, $users, $roles)
        );
    }
}
