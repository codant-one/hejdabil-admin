<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Client;
use App\Models\VehicleClient;
use App\Models\AgreementClient;

class UpdatePhoneToClients extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clients:update-phone';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update phone numbers for clients';

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
        self::clients();
        self::vehicle_clients();
        self::agreement_clients();

        return 0;
    }

    private function clients() {
        
        $clients = Client::withTrashed()->get();

        foreach ($clients as $client) {
            // Update phone numbers logic here
            $this->info("Update clients with phone: " . $client->phone);
             
        }

        $this->info("Update clients");

        return 0;
    }

     private function agreement_clients() {
        
        $agreementClients = AgreementClient::all();

        foreach ($agreementClients as $agreementClient) {
            // Update phone numbers logic here
            $this->info("Update agreement clients with phone: " . $agreementClient->phone);
             
        }

        $this->info("Update agreement clients");

        return 0;
    }

    private function vehicle_clients() {
        
        $vehicleClients = VehicleClient::all();

        foreach ($vehicleClients as $vehicleClient) {
            // Update phone numbers logic here
            $this->info("Update vehicle clients with phone: " . $vehicleClient->phone);
             
        }

        $this->info("Update vehicle clients");

        return 0;
    }
}
