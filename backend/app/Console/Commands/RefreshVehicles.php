<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

use App\Models\Vehicle;
use App\Models\Agreement;

class RefreshVehicles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vehicles:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh vehicle data';

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
        $this->refreshVehicles();

        return 0;
    }

    private function refreshVehicles(): void
    {
        // agreements purchase
        $agreements = Agreement::with('vehicle_client.vehicle')->where('agreement_type_id', 2)->get();

        foreach ($agreements as $agreement) {
            
            $vehicle = Vehicle::where('reg_num', $agreement->vehicle_client->vehicle->reg_num)->first();

            if (is_null($vehicle->purchase_price)) {
                $vehicle->purchase_price = $agreement->price;
                $vehicle->save();

                $this->info(
                    'Agreement: ' . $agreement->id . ' - ' .
                    'Vehicle: ' . $agreement->vehicle_client->vehicle->reg_num. ' - ' . 
                    'Price: ' . $agreement->price
                );
            }
            
        }

    }

}
