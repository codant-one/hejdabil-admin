<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use Carbon\Carbon;

use App\Models\Supplier;

class UpdateSupplierPlanDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'suppliers:update-plan-date';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the supplier plan date';

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
        $today = now();

        $suppliers = Supplier::with('plan')
            ->where('state_id', 2)// active suppliers
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->whereDate('end_date', $today) // end_date is in the past or today
            ->get();

        $suppliers->map(function ($supplier) {

            $startDate = Carbon::parse($supplier->start_date);
            $nextStartDate = $supplier->is_yearly
                ? $startDate->copy()->addYear()
                : $startDate->copy()->addMonth();

            $endDate = Carbon::parse($supplier->end_date);
            $nextEndDate = $supplier->is_yearly
                ? $endDate->copy()->addYear()
                : $endDate->copy()->addMonth();

            $supplier->start_date = $nextStartDate;
            $supplier->end_date = $nextEndDate;
            $supplier->next_billing_date = $nextEndDate;
            $supplier->save();
        });

        $this->info("Update suppliers state");

        return 0;
    }
}
