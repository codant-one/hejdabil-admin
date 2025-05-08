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
        $states = ['Inaktiv', 'Aktiv', 'Publicerad', 'Väntande', 'Borttagen', 'Avvisad', 'Betalad', 'Förfallna', 'Kredit'];

        foreach($states as $state){
            State::updateOrCreate([
                'name' => $state,
                'label' => strtolower($state)
            ]);
        }

        $this->info("Add new states");

        return 0;
    }
}
