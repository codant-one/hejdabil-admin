<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

use App\Events\UserNotificationEvent;

use Carbon\Carbon;
use PDF;

use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\Config;
use App\Models\Invoice;
use App\Models\SettingColor;
use App\Models\SmsMessage;
use App\Models\Notification;

class GenerateSupplierBilling extends Command
{
    // Note: In this project, supplier invoices are stored in the SupplierInvoice model (supplier_invoices table).
    private const SWEDISH_MONTHS = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Mars',
        4 => 'April',
        5 => 'Maj',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Augusti',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'December',
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'supplier:generate-billing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate supplier billing';

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
        $today = Carbon::today();

        $successCount = 0;
        $errorCount = 0;
        $skippedCount = 0;

        do {
            $createdInPass = 0;

            $suppliers = $this->getSuppliers($today);

            foreach ($suppliers as $supplier) {
                try {
                    $created = $this->processBilling($supplier);

                    if ($created) {
                        $successCount++;
                        $createdInPass++;
                    } else {
                        $skippedCount++;
                    }
                } catch (\Throwable $e) {
                    $errorCount++;

                    Log::error('Failed to generate supplier billing', [
                        'supplier_id' => $supplier->id,
                        'message' => $e->getMessage(),
                    ]);
                }
            }
        } while ($createdInPass > 0);

        $this->printSummary($successCount, $skippedCount, $errorCount);

        return 0;
    }

    private function getSuppliers(Carbon $today)
    {
        return Supplier::with('plan')
            ->where('state_id', 2)
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->whereNotNull('next_billing_date')
            ->whereDate('next_billing_date', '<=', $today)
            ->get();
    }

    private function processBilling(Supplier $supplier): bool
    {
        return DB::transaction(function () use ($supplier) {
            $lockedSupplier = Supplier::where('id', $supplier->id)->lockForUpdate()->first();

            $billingPeriod = $supplier->is_yearly
                ? Carbon::parse($lockedSupplier->next_billing_date)->format('Y')
                : Carbon::parse($lockedSupplier->next_billing_date)->format('Y-m');

            $alreadyBilled = SupplierInvoice::where('supplier_id', $lockedSupplier->id)
                ->where('billing_period', $billingPeriod)
                ->exists();

            if ($alreadyBilled) {
                return false;
            }

            $maxInvoiceId = SupplierInvoice::where('supplier_id', $supplier->id)
                ->lockForUpdate()
                ->max('invoice_id');

            $invoiceId = ((int) $maxInvoiceId) + 1;

            $pricePlan = $supplier->is_yearly
                ? $supplier->plan->price_annual
                : $supplier->plan->price_month;

            $smsSummary = [
                'count' => 0,
                'unit_price' => $supplier->sms_price ?? 1.0,
                'total' => 0.0,
                'from' => null,
                'to' => null,
            ];

            if ($supplier->is_yearly === 0) {// mensual
                $filterEnd = Carbon::parse($lockedSupplier->next_billing_date)->endOfDay();
                $filterStart = (clone $filterEnd)->subMonth()->startOfDay();

                $totalSMS = $this->getTeamDocumentTotalCount(
                    SmsMessage::query()->where('supplier_id', $supplier->id)->where('billable_count', '>', 0),
                    $filterStart,
                    $filterEnd
                );

                $smsSummary = [
                    'count' => $totalSMS,
                    'unit_price' => $supplier->sms_price ?? 1.0,
                    'total' => round($totalSMS * ($supplier->sms_price ?? 1.0), 2),
                    'from' => $filterStart,
                    'to' => $filterEnd,
                ];
            }

            $details = $this->buildBillingDetail($supplier, $lockedSupplier->next_billing_date, $pricePlan, $smsSummary);

            $tax = 25;
            $price = $pricePlan + (($supplier->is_yearly === 0) ? (float) ($smsSummary['total'] ?? 0) : 0);
            $amountTax = round(($price * $tax) / 100, 2);
            $subtotal = $price;
            $total = $price + $amountTax;

            $billing = SupplierInvoice::create([
                'user_id' => null,
                'supplier_id' => $supplier->id,
                'state_id' => 4,
                'billing_period' => $billingPeriod,
                'invoice_id' => $invoiceId,
                'invoice_date' => Carbon::now(),
                'due_date' => Carbon::now()->addDays(10),
                'payment_terms' => '10 dagar netto',
                'terms_and_conditions' => 'Efter förfallodagen debiteras ränta enligt räntelagen',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'amount_tax' => $amountTax,
                'total' => $total,
                'amount_discount' => 0,
                'rabatt' => 0,
                'discount' => 0,
                'detail' => $details,
            ]);

            // Advance from the current billing cursor to avoid re-billing the same month.
            $nextBillingDate = Carbon::parse($lockedSupplier->next_billing_date);
            $nextBillingDate = $supplier->is_yearly
                ? $nextBillingDate->addYear()
                : $nextBillingDate->addMonth();

            $lockedSupplier->next_billing_date = $nextBillingDate;
            $lockedSupplier->save();

            $this->generatePdf($billing);
            $this->sendNotification($billing);
            $this->sendEmail($billing);

            return true;
        });
    }

    private function buildBillingDetail(Supplier $supplier, $nextBillingDate, float $price, array $smsSummary = []): string
    {
        $supplier = Supplier::with(['user.userDetail'])->find($supplier->id);
        $formattedPrice = number_format($price, 2, '.', '');
        $periodDate = Carbon::parse($nextBillingDate);
        $period = $supplier->is_yearly
            ? (string) $periodDate->year
            : self::SWEDISH_MONTHS[(int) $periodDate->month] . ' ' . $periodDate->year;

        $details = [
            [
                ['id' => 1, 'value' => 'Bilflogg - Systemabonnemang (' . $supplier->plan->name . ')'],
                ['id' => 2, 'value' => 1],
                ['id' => 3, 'value' => $formattedPrice],
                ['id' => 4, 'value' => $formattedPrice],
                ['id' => 5, 'value' => 0],
                ['id' => 6, 'value' => false],
            ],
            [
                ['note' => 'Period: ' . $period],
            ],
        ];

        if ($supplier->is_yearly === 0) {// mensual
            $totalSMS = (int) ($smsSummary['count'] ?? 0);

            if ($totalSMS > 0) {
                $unitPrice = (float) ($smsSummary['unit_price'] ?? $supplier->sms_price ?? 1.0);
                $priceSMS = number_format((float) ($smsSummary['total'] ?? round($totalSMS * $unitPrice, 2)), 2, '.', '');
                $from = $smsSummary['from'] instanceof Carbon ? $smsSummary['from'] : null;
                $to = $smsSummary['to'] instanceof Carbon ? $smsSummary['to'] : null;
                $time = ($from ? $from->format('y.m.d') : '-') . ' - ' . ($to ? $to->format('y.m.d') : '-');
                $companyName = $supplier->user?->userDetail?->company ?? 'Okänd';

                array_push(
                    $details,
                    [
                        ['id' => 1, 'value' => 'eSign - SMS (' . $companyName . ') ' . $time],
                        ['id' => 2, 'value' => $totalSMS],
                        ['id' => 3, 'value' => number_format($unitPrice, 2, '.', '')],
                        ['id' => 4, 'value' => $priceSMS],
                        ['id' => 5, 'value' => 0],
                        ['id' => 6, 'value' => false],
                    ]
                );
            }
        }
        
        return json_encode($details, true);
    }

    private function generatePdf(SupplierInvoice $billing): void
    {
        $supplier = Supplier::with(['user'])->find($billing->supplier_id);
        $billing = SupplierInvoice::with(['supplier.user', 'user.userDetail'])->find($billing->id);
        $types = Invoice::all();
        $invoices = [];

        $configCompany = Config::getByKey('company') ?? ['value' => '[]'];
        $configLogo = Config::getByKey('logo') ?? ['value' => '[]'];
        $configColor = Config::getByKey('color') ?? ['value' => '[]'];
        $configBillings = Config::getByKey('billings') ?? ['value' => '[]'];

        $getValue = function ($cfg) {
            if (is_array($cfg)) {
                return $cfg['value'] ?? '[]';
            }

            if (is_object($cfg) && isset($cfg->value)) {
                return $cfg->value;
            }

            return '[]';
        };

        $decodeSafe = function ($raw) {
            $decoded = json_decode($raw);

            if (is_string($decoded)) {
                $decoded = json_decode($decoded);
            }

            if (!is_object($decoded)) {
                $decoded = (object) [];
            }

            return $decoded;
        };

        $company = $decodeSafe($getValue($configCompany));
        $logoObj = $decodeSafe($getValue($configLogo));
        $colorObj = $decodeSafe($getValue($configColor));
        $billingsObj = $decodeSafe($getValue($configBillings));

        $company->logo = $logoObj->logo ?? null;
        $company->type = $billingsObj->type ?? 1;
        $company->theme = $colorObj->theme ?? 0;

        $colorSettingId = $colorObj->setting_color_id ?? null;

        if ($colorSettingId) {
            $color = SettingColor::find($colorSettingId);

            $company->primary_color = $color->primary ?? '#4BBDAA';
            $company->secondary_color = $color->secondary ?? '#E8F6F4';
        } else {
            $company->primary_color = $colorObj->primary_color ?? '#4BBDAA';
            $company->secondary_color = $colorObj->secondary_color ?? '#E8F6F4';
        }

        $details = json_decode($billing->detail, true);

        foreach ($details as $row) {
            $invoices[] = $row;
        }

        if (!file_exists(storage_path('app/public/pdfs'))) {
            mkdir(storage_path('app/public/pdfs'), 0755, true);
        }

        PDF::loadView('pdfs.invoices.suppliers', compact('company', 'billing', 'types', 'invoices'))
            ->save(storage_path('app/public/pdfs') . '/' . Str::slug($supplier->user->name . ' ' . $supplier->user->last_name) . '-faktura-' . $billing->invoice_id . '.pdf');

        $billing->file = 'pdfs/' . Str::slug($supplier->user->name . ' ' . $supplier->user->last_name) . '-faktura-' . $billing->invoice_id . '.pdf';
        $billing->update();
    }

    private function printSummary(int $successCount, int $skippedCount, int $errorCount): void
    {
        $this->info("Invoices generated correctly: {$successCount}");
        $this->info("Invoices skipped (already billed): {$skippedCount}");

        if ($errorCount > 0) {
            $this->error("Errors during generation: {$errorCount}");
        }
    }

    private function getTeamDocumentTotalCount(
        $query,
        ?Carbon $filterStart = null,
        ?Carbon $filterEnd = null,
    ): int {
        $filteredQuery = clone $query;

        if ($filterStart && $filterEnd) {
            $filteredQuery = (clone $filteredQuery)
                ->whereNotNull('created_at')
                ->whereBetween('created_at', [
                    $filterStart->toDateTimeString(),
                    $filterEnd->toDateTimeString(),
                ]);
        }

        return (clone $filteredQuery)->count();
    }

    private function sendNotification(SupplierInvoice $billing): void
    {
        // Prepare data for notification  
        $period = $billing->supplier->is_yearly === 0 ? 'månadsfaktura' : 'årsvis faktura';     
        $title = 'Ny faktura tillgänglig';
        $subtitle = "Din {$period} har skapats och finns nu tillgänglig.";
        $text = 'Din faktura har skapats. Gå till Inställningar → Plan → Betalningshistorik för att granska och betala fakturan.';
        $color = 'primary';
        $icon = 'custom-facture';
        $route = '/dashboard/panel';//la ruta que no teniamos

        $dbNotification = Notification::create([
            'user_id' => $billing->supplier->user_id,
            'notification_id' => $billing->id,
            'title' => $title,
            'subtitle' => $subtitle,
            'text' => $text,
            'color' => $color,
            'icon' => $icon,
            'route' => $route,
            'read' => false,
        ]);

        // Preparar el mensaje de notificación para WebSocket
        $message = (object) [
            'id' => $dbNotification->id,
            'title' => $title,
            'subtitle' => $subtitle,
            'time' => now()->format('H:i:s'),
            'img' => null,
            'color' => $color,
            'icon' => $icon,
            'text' => $text,
            'route' => $route,
            'read' => false,
        ];

        // Enviar via WebSocket
        // Notificación privada
        $evento = new UserNotificationEvent($message, $billing->supplier->user_id);
        Event::dispatch($evento);
    }

    private function sendEmail(SupplierInvoice $billing): void
    {
        $billing = SupplierInvoice::with(['supplier.user'])->find($billing->id);

        if (!$billing || !$billing->supplier || !$billing->supplier->user || !$billing->supplier->user->email) {
            return;
        }

        $configCompany = Config::getByKey('company') ?? ['value' => '[]'];
        $configLogo    = Config::getByKey('logo')    ?? ['value' => '[]'];
        
        // Extraer el "value" soportando array u object
        $getValue = function ($cfg) {
            if (is_array($cfg)) 
                return $cfg['value'] ?? '[]';
            if (is_object($cfg) && isset($cfg->value))
                return $cfg->value;
            return '[]';
        };
        
        $companyRaw = $getValue($configCompany);
        $logoRaw    = $getValue($configLogo);
        
        $decodeSafe = function ($raw) {
            $decoded = json_decode($raw);

            if (is_string($decoded))
                $decoded = json_decode($decoded);
        
            if (!is_object($decoded)) 
                $decoded = (object) [];
        
            return $decoded;
        };
        
        $company = $decodeSafe($companyRaw);
        $logoObj = $decodeSafe($logoRaw);
        
        $company->logo = $logoObj->logo ?? null;
        $logo = $company->logo ? asset('storage/' . $company->logo) : null;

        $userName = trim(($billing->supplier->user->name ?? '') . ' ' . ($billing->supplier->user->last_name ?? ''));
        $attachmentPath = $billing->file ? storage_path('app/public/' . ltrim($billing->file, '/')) : null;

        $data = [
            'company' => $company,
            'billing' => $billing,
            'supplier' => $billing->supplier,
            'user' => $userName !== '' ? $userName : ($billing->supplier->user->email ?? ''),
            'title' => 'Ny faktura tillgänglig',
            'icon' => asset('/images/invoices.png'),
            'logo' => $logo
        ];

        $subject = 'Ny faktura tillgänglig';

        \Mail::send(
            'emails.invoices.suppliers',
            $data,
            function ($message) use ($billing, $attachmentPath, $subject) {
                $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
                $message->to($billing->supplier->user->email)->subject($subject);

                if ($attachmentPath && is_file($attachmentPath)) {
                    $message->attach($attachmentPath, [
                        'as' => \Illuminate\Support\Str::of($billing->file)->afterLast('/'),
                        'mime' => 'application/pdf',
                    ]);
                }
            }
        );
    }
}
