<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Supplier;

class UpdateSwishInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swish:update-info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Swish info for suppliers';

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
        self::updateCSR();
        self::updatePEM();

        return 0;
    }

    private function updateCSR() {
        $suppliers = Supplier::all();

        foreach ($suppliers as $supplier) {
            if($supplier->csr_url) {
                $supplier = Supplier::find($supplier->id);
                $supplier->csr_at = $supplier->updated_at;
                $supplier->save();
            }
        }

        $this->info("Update CSR timestamps");
    }

    private function updatePEM() {
        $suppliers = Supplier::all();

        foreach ($suppliers as $supplier) {
            if($supplier->pem_url) {
                $supplier = Supplier::find($supplier->id);
                $supplier->pem_at = $supplier->updated_at;
                $supplier->save();
            }
        }
        
        $this->info("Update PEM timestamps");
    }
}
