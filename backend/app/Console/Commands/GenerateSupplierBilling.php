<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Supplier;

class GenerateSupplierBilling extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'supplier:generate-billing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate supplier billing';

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
        $suppliers = Supplier::all();

        foreach($suppliers as $supplier) {
            // Generate billing for each supplier
        }

        $this->info("Generated supplier billing");

        return 0;
    }
}
