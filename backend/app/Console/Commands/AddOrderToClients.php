<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Client;

class AddOrderToClients extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clients:update-order';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add order ids to clients by suppliers';

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
        $clients = Client::withTrashed()->orderBy('supplier_id')->get();

        $clientsGroup = $clients->groupBy('supplier_id');

        foreach ($clientsGroup as $supplierId => $clients) {
            $orderId = 1;
            foreach ($clients as $client) {
                $client->order_id = $orderId++;
                $client->save();
            }
        }

        $this->info("Update clients");

        return 0;
    }
}
