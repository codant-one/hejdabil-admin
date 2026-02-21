<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ClientType;

class AddOrUpdateClientType extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'client-types:add-client-types';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add new client types to table';

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
        $clientTypes = ['Privat', 'Företag', 'Utländsk kund'];
        $labels = ['private', 'company', 'foreign'];

        foreach($clientTypes as $key => $clientType) {
            ClientType::updateOrCreate(
                ['name' => $clientType],
                ['label' => strtolower($labels[$key])] 
            );
        }

        $this->info("Add or update client types completed successfully.");

        return 0;
    }
}
