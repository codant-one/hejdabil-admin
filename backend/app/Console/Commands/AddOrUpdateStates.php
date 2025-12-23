<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\State;

class AddOrUpdateStates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'states:add-states';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add new states to table';

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
        $states = ['Inaktiv', 'Aktiv', 'Publicerad', 'Obetald', 'Borttagen', 'Avvisad', 'Betald', 'Förfallna', 'Krediterad', 'På lager', 'På annons', 'Såld', 'Förmedlingsbil', 'Väntande'];
        $labels = ['Inactive', 'Active', 'Published', 'Unpaid', 'Removed', 'Rejected', 'Paid', 'Overdue', 'Credit', 'In stock', 'Announced', 'Sold', 'Rented', 'Pending'];

        foreach($states as $key => $state) {
            State::updateOrCreate(
                ['name' => $state],
                ['label' => strtolower($labels[$key])] 
            );
        }

        $this->info("Add or update states");

        return 0;
    }
}
