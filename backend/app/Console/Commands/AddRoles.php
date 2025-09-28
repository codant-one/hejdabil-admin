<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Role;

class AddRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'roles:add-roles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add new role to table';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $roles = ['SuperAdmin', 'Administrator', 'Operator', 'Supplier', 'User'];

        $this->info("Finding this role on Roles table");   

        foreach($roles as $role){
            Role::firstOrCreate(
                ['name' => $role]
            );
        }

        $this->info("Add new roles on Roles table");

        return 0;
    }
}
