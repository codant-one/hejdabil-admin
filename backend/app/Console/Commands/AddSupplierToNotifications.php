<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Notification;
use App\Models\Supplier;

class AddSupplierToNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:add-supplier';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add supplier ids to notifications';

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
        $notifications = Notification::all();

        foreach ($notifications as $notification) {
            $supplier = Supplier::where('user_id', $notification->user_id)->first();
            if($supplier) {
                $notification->supplier_id = $supplier->boss_id === null ? $supplier->id : $supplier->boss_id;
                $notification->save();
            }
        }

        $this->info("Update notifications");

        return 0;
    }
}
