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
        $states = ['Inaktiv', 'Aktiv', 'Publicerad', 'Väntande', 'Borttagen', 'Avvisad', 'Betalad', 'Förfallna', 'Kredit', 'På lager', 'På annons', 'Såld', 'Förmedlingsbil'];
        $labels = ['Inactive', 'Active', 'Published', 'Pending', 'Removed', 'Rejected', 'Paid', 'Overdue', 'Credit', 'In stock', 'Announced', 'Sold', 'Rented'];

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
