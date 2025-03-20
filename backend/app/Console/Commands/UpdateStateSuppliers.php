<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Supplier;

class UpdateStateSuppliers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'suppliers:update-state';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the supplier status to deleted when the deleted_at field exists';

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
        $suppliers = Supplier::onlyTrashed()->get();

        $suppliers->map(function ($supplier) {
            $supplier->state_id = 5;
            $supplier->save();
        });

        $this->info("Update suppliers state");

        return 0;
    }
}
