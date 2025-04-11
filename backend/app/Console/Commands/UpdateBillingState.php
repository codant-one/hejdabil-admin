<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Billing;

class UpdateBillingState extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billings:update-state';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the billing state';

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
        $billings = Billing::where('due_date', '<', now())
                           ->where('state_id', 4)
                           ->get();

        $billings->map(function ($billing) {
            $billing->state_id = 8;
            $billing->save();
        });

        $this->info("Update billings state");

        return 0;
    }
}
