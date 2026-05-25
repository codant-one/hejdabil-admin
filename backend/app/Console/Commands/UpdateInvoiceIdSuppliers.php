<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use App\Models\Billing;
use App\Models\Setting;
use App\Models\SettingBilling;
use App\Models\Supplier;
use App\Models\UserDetails;

class UpdateInvoiceIdSuppliers extends Command
{
    private const BILLING_SMS_COMPANY_PLACEHOLDER = '{Företagsnamn}';
    private const DEFAULT_BILLING_SMS_MESSAGE = 'Du har fått en faktura från {Företagsnamn}.';
    private const DEFAULT_BILLING_COMPANY_NAME = 'Billogg Sverige AB';
    private const DEFAULT_BILLING_TERMS_AND_CONDITIONS = 'Efter förfallodagen debiteras ränta enligt räntelagen';

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
            $supplierId = (int) $row->supplier_id;
            $nextInvoiceId = ((int) $row->last_invoice_id) + 1;

            $setting = Setting::query()
                ->where('supplier_id', $supplierId)
                ->first();

            $settingBilling = $setting?->setting_billing_id
                ? SettingBilling::query()->find($setting->setting_billing_id)
                : null;

            if ($settingBilling) {
                $updates = [
                    'invoice_id' => $nextInvoiceId,
                    ...$this->missingSettingBillingDefaults($settingBilling, $supplierId, $nextInvoiceId),
                ];

                $settingBilling->update($updates);

                $updatedCount++;
                $message = count($updates) > 1
                    ? "Supplier {$supplierId}: next invoice_id set to {$nextInvoiceId} and missing default values were filled."
                    : "Supplier {$supplierId}: next invoice_id set to {$nextInvoiceId}.";

                $this->info($message);

                return;
            }

            $settingUserId = $this->resolveSettingUserId($setting, $supplierId);

            if (!$settingUserId) {
                $skippedCount++;
                $this->warn("Skipped supplier {$supplierId}: unable to resolve a user_id for settings.");

                return;
            }

            $settingBilling = SettingBilling::query()->create(
                $this->defaultSettingBillingValues($supplierId, $nextInvoiceId)
            );

            if ($setting) {
                $setting->update([
                    'setting_billing_id' => $settingBilling->id,
                ]);
            } else {
                Setting::query()->create([
                    'user_id' => $settingUserId,
                    'supplier_id' => $supplierId,
                    'setting_billing_id' => $settingBilling->id,
                ]);
            }

            $updatedCount++;
            $this->info("Supplier {$supplierId}: default setting_billing created and next invoice_id set to {$nextInvoiceId}.");
        });

        $this->newLine();
        $this->info("Updated {$updatedCount} supplier billing settings.");

        if ($skippedCount > 0)
            $this->warn("Skipped {$skippedCount} suppliers without a usable settings owner.");

        return 0;
    }

    private function resolveSettingUserId(?Setting $setting, int $supplierId): ?int
    {
        if ($setting?->user_id) {
            return (int) $setting->user_id;
        }

        $supplierUserId = Supplier::withTrashed()
            ->whereKey($supplierId)
            ->value('user_id');

        return $supplierUserId ? (int) $supplierUserId : null;
    }

    private function defaultSettingBillingValues(int $supplierId, int $nextInvoiceId): array
    {
        return [
            'type' => 1,
            'due_dates' => 5,
            'terms_and_conditions' => self::DEFAULT_BILLING_TERMS_AND_CONDITIONS,
            'sms_message' => $this->replaceCompanyPlaceholder(
                self::DEFAULT_BILLING_SMS_MESSAGE,
                $this->resolveBillingCompanyName($supplierId)
            ),
            'send_reminder' => 1,
            'send_notifications' => 0,
            'invoice_id' => $nextInvoiceId,
        ];
    }

    private function missingSettingBillingDefaults(SettingBilling $settingBilling, int $supplierId, int $nextInvoiceId): array
    {
        $defaults = $this->defaultSettingBillingValues($supplierId, $nextInvoiceId);
        $missingDefaults = [];

        foreach ($defaults as $field => $defaultValue) {
            if ($field === 'invoice_id') {
                continue;
            }

            $currentValue = $settingBilling->{$field};

            if (is_null($currentValue) || (is_string($currentValue) && trim($currentValue) === '')) {
                $missingDefaults[$field] = $defaultValue;
            }
        }

        return $missingDefaults;
    }

    private function resolveBillingCompanyName(int $supplierId): string
    {
        $supplierUserId = Supplier::withTrashed()
            ->whereKey($supplierId)
            ->value('user_id');

        if (!$supplierUserId) {
            return self::DEFAULT_BILLING_COMPANY_NAME;
        }

        $companyName = UserDetails::query()
            ->where('user_id', $supplierUserId)
            ->value('company');

        $normalizedCompany = trim((string) $companyName);

        return $normalizedCompany !== ''
            ? $normalizedCompany
            : self::DEFAULT_BILLING_COMPANY_NAME;
    }

    private function replaceCompanyPlaceholder(string $message, ?string $company): string
    {
        $normalizedCompany = trim((string) $company) ?: self::DEFAULT_BILLING_COMPANY_NAME;

        return str_replace(self::BILLING_SMS_COMPANY_PLACEHOLDER, $normalizedCompany, $message);
    }
}
