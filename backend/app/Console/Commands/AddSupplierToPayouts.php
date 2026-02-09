<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Payout;
use App\Models\Supplier;

class AddSupplierToPayouts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payouts:add-supplier';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add supplier ids to payouts';

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
        $payouts = Payout::all();

        foreach ($payouts as $payout) {
           $supplier = Supplier::where('user_id', $payout->user_id)->first();
            if($supplier) {
                $payout->supplier_id = $supplier->boss_id === null ? $supplier->id : $supplier->boss_id;
                $payout->save();
            }
        }

        $this->info("Update payouts");

        return 0;
    }
}
