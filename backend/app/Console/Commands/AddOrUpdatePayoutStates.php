<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PayoutState;

class AddOrUpdatePayoutStates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payout-states:add-states';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add new payout states to table';

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
        $states = ['Skapad', 'Skickad', 'Väntande', 'Betald', 'Misslyckad', 'Avbruten', 'Utgången'];
        $labels = ['Created', 'Sent', 'Pending', 'Paid', 'Failed', 'Cancelled', 'Expired'];

        foreach($states as $key => $state) {
            PayoutState::updateOrCreate(
                ['name' => $state],
                ['label' => strtolower($labels[$key])] 
            );
        }

        $this->info("Add or update payout states");

        return 0;
    }
}
