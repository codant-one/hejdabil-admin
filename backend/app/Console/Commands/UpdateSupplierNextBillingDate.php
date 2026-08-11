<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use Carbon\Carbon;

use App\Models\Supplier;

class UpdateSupplierNextBillingDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'supplier:update-next-billing-date';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update next_billing_date for suppliers missing it';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $updated = 0;

        Supplier::query()
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->whereNull('next_billing_date')
            ->chunkById(200, function ($suppliers) use (&$updated) {
                foreach ($suppliers as $supplier) {
                    $supplier->update([
                        'next_billing_date' => Carbon::parse($supplier->start_date)->copy()->addDays(5),
                    ]);

                    $updated++;
                }
            });

        $this->info("Updated suppliers: {$updated}");

        return 0;
    }
}
