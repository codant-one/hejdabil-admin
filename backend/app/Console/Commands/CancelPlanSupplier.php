<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;

use Carbon\Carbon;
use App\Models\Supplier;

class CancelPlanSupplier extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'suppliers:cancel-plan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancel plan for suppliers with expired grace period';

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
        self::cancelPlan();

        return 0;
    }

    private function cancelPlan()
    {
        $today = Carbon::today();

        $suppliers = 
            Supplier::with('plan')
                ->where('state_id', 2)
                ->whereNotNull('cancellation_date')
                ->whereNotNull('grace_end_date')
                ->whereDate('grace_end_date', '>=', $today)// ya paso la fecha de gracia
                ->get();

        foreach($suppliers as $supplier) {
            $supplier->is_subscription_active = 0;//desactiva la suscripción
            $supplier->save();
        }    

        return 0;
    }
}
