<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use App\Models\Billing;
use App\Models\Setting;
use App\Models\SettingBilling;

class UpdateInvoiceIdSuppliers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billings:update-invoice-id-suppliers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync supplier setting_billings.invoice_id with the next invoice number from billings';

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
        $latestInvoiceIds = Billing::query()
            ->select('supplier_id', DB::raw('MAX(invoice_id) as last_invoice_id'))
            ->whereNotNull('supplier_id')
            ->groupBy('supplier_id')
            ->orderBy('supplier_id')
            ->get();

        $updatedCount = 0;
        $skippedCount = 0;

        $latestInvoiceIds->each(function ($row) use (&$updatedCount, &$skippedCount) {
            $setting = Setting::query()
                ->where('supplier_id', $row->supplier_id)
                ->first();

            if (!$setting?->setting_billing_id) {
                $skippedCount++;
                $this->warn("Skipped supplier {$row->supplier_id}: missing settings or setting_billing_id.");

                return;
            }

            $nextInvoiceId = ((int) $row->last_invoice_id) + 1;

            $updated = SettingBilling::query()
                ->whereKey($setting->setting_billing_id)
                ->update([
                    'invoice_id' => $nextInvoiceId,
                ]);

            if (!$updated) {
                $skippedCount++;
                $this->warn("Skipped supplier {$row->supplier_id}: setting_billing {$setting->setting_billing_id} was not found.");

                return;
            }

            $updatedCount++;
            $this->info("Supplier {$row->supplier_id}: next invoice_id set to {$nextInvoiceId}.");
        });

        $this->newLine();
        $this->info("Updated {$updatedCount} supplier billing settings.");

        if ($skippedCount > 0)
            $this->warn("Skipped {$skippedCount} suppliers without a usable setting_billing record.");

        return 0;
    }
}
