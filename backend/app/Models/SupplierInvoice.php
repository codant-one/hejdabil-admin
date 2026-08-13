<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

use App\Jobs\SendEmailJob;
use Carbon\Carbon;
use PDF;

use App\Models\Invoice;
use App\Models\Supplier;
use App\Models\UserDetails;
use App\Models\User;
use App\Models\Config;
use App\Models\Setting;
use App\Models\SettingColor;
use App\Models\SettingBilling;

class SupplierInvoice extends Model
{
    use HasFactory, SoftDeletes;

    private const BILLING_SMS_COMPANY_PLACEHOLDER = '{Företagsnamn}';
    private const DEFAULT_BILLING_SMS_MESSAGE = 'Du har fått en faktura från {Företagsnamn}.';
    private const DEFAULT_BILLING_COMPANY_NAME = 'Bilflogg Sverige AB';
    private const DEFAULT_BILLING_TERMS_AND_CONDITIONS = 'Efter förfallodagen debiteras ränta enligt räntelagen';

    protected $guarded = [];

    /**** Relationship ****/
    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id')->withTrashed();
    }

    public function supplier() {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }

    public function state() {
        return $this->belongsTo(State::class, 'state_id', 'id');
    }

    /**** Scopes ****/
    public function scopeWhereSearch($query, $search) {
        $query->where(function($query) use ($search) {
            $query->whereHas('client', function ($q) use ($search) {
                $q->withTrashed()
                  ->where(function ($query) use ($search) {
                      $query->where('fullname', 'LIKE', '%' . $search . '%');
                  });
            })
            ->orWhereHas('supplier', function ($q) use ($search) {
                $q->withTrashed()
                  ->whereHas('user', function ($q) use ($search) {
                      $q->withTrashed()
                        ->where(function ($query) use ($search) {
                            $query->where('name', 'LIKE', '%' . $search . '%')
                                  ->orWhere('last_name', 'LIKE', '%' . $search . '%')
                                  ->orWhere('email', 'LIKE', '%' . $search . '%');
                        });
                  });
            })
            ->orWhereHas('user', function ($uq) use ($search) {
                $uq->where(function ($inner) use ($search) {
                    $inner->where('name', 'LIKE', '%' . $search . '%')
                         ->orWhere('last_name', 'LIKE', '%' . $search . '%')
                         ->orWhere('email', 'LIKE', '%' . $search . '%')
                         ->orWhereRaw("CONCAT(name, ' ', last_name) LIKE ?", ['%' . $search . '%']);
                });
            })
            ->orWhere('invoice_id', 'LIKE', '%' . $search . '%')
            ->orWhere('invoice_date', 'LIKE', '%' . $search . '%')
            ->orWhere('due_date', 'LIKE', '%' . $search . '%')
            ->orWhere('detail', 'LIKE', '%' . $search . '%')
            ->orWhere('total', 'LIKE', '%' . $search . '%');
        });
    }

    public function scopeWhereOrder($query, $orderByField, $orderBy) {
        $query->orderByRaw('(IFNULL('. $orderByField .', id)) '. $orderBy);
    }

    public function scopeApplyFilters($query, array $filters) {
        $filters = collect($filters);

        if ($filters->get('supplier_id') !== null) {
            $query->where('supplier_id', $filters->get('supplier_id'));
        } else if(Auth::check() && Auth::user()->getRoleNames()[0] === 'Supplier') {
            $query->where('supplier_id', Auth::user()->supplier->id);
        } else if(Auth::check() && Auth::user()->getRoleNames()[0] === 'User') {
            $query->where('supplier_id', Auth::user()->supplier->boss_id);
        }

        if ($filters->get('search')) {
            $query->whereSearch($filters->get('search'));
        }

        if ($filters->get('state_id') !== null) {
            if($filters->get('state_id') === '7') {
                $query->where(function($q) {
                    $q->where('state_id', 7)
                      ->orWhere('state_id', 9);
                });
            } else {
                $query->where('state_id', $filters->get('state_id'));
            }
        }

        if ($filters->get('date_from') && $filters->get('date_to')) {
            $filter = [
                [Carbon::parse($filters->get('date_from'))->format('Y-m-d').' 00:00:00'],
                [Carbon::parse($filters->get('date_to'))->format('Y-m-d').' 23:59:59']
            ];
            $query->whereBetween('invoice_date', $filter);// fecha de factura
        }
        
        if ($filters->get('orderByField') || $filters->get('orderBy')) {
            $field = $filters->get('orderByField') ? $filters->get('orderByField') : 'order_id';
            $orderBy = $filters->get('orderBy') ? $filters->get('orderBy') : 'asc';
            $query->whereOrder($field, $orderBy);
        }
    }

    public function scopePaginateData($query, $limit) {
        if ($limit == 'all') {
            return collect(['data' => $query->get()]);
        }

        return $query->paginate($limit);
    }

    /**** Public methods ****/
    public static function createCredit($billing) {
        $maxInvoiceId = self::where('supplier_id', $billing->supplier_id)->max('invoice_id');
        $invoiceId = ((int) ($maxInvoiceId ?? 0)) + 1;

        $array = json_decode($billing->detail, true);

        if (is_array($array)) {
            foreach ($array as &$group) {
                if (!is_array($group))
                    continue;

                foreach ($group as &$item) {
                    if (!is_array($item) || !isset($item['id']))
                        continue;

                    if ($item['id'] == 3 || $item['id'] == 4) {
                        $numericValue = is_numeric($item['value']) ? (float)$item['value'] : null;
                        if ($numericValue !== null) {
                            $item['value'] = '-' . ltrim((string) $item['value'], '-');
                        }
                    }
                }
                unset($item);
            }
            unset($group);
        }

        // Marcar la factura original como crédito
        $oldBilling = self::find($billing->id);
        $oldBilling->update([
            'is_credit' => 1,
            'state_id' => 7
        ]);

        $billing = self::create([
            'user_id' => Auth::user()->id,
            'supplier_id' => $billing->supplier_id,
            'state_id' => 9,
            'billing_period' => $billing->billing_period,
            'credit_id' => $billing->id,
            'invoice_id' =>  $invoiceId,
            'invoice_date' => now(),
            'due_date' =>  now(),
            'payment_terms' =>  '0 dagar netto',
            'terms_and_conditions' => $billing->terms_and_conditions,
            'rabatt' =>  $billing->rabatt,
            'discount' =>  $billing->discount,
            'amount_discount' =>  $billing->amount_discount,
            'amount_tax' => '-' . $billing->amount_tax,
            'subtotal' => '-' . $billing->subtotal,
            'tax' => $billing->tax,
            'total' =>  '-' . $billing->total,
            'detail' => json_encode($array, true)
        ]);    

        $supplier = Supplier::with(['user'])->find($billing->supplier_id);
        $billing = self::with(['supplier.user', 'user.userDetail'])->find($billing->id);
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
            ->save(storage_path('app/public/pdfs') . '/' . Str::slug($supplier->user->name . ' ' . $supplier->user->last_name) . '-kredit-faktura-' . $billing->invoice_id . '.pdf');

        $billing->file = 'pdfs/' . Str::slug($supplier->user->name . ' ' . $supplier->user->last_name) . '-kredit-faktura-' . $billing->invoice_id . '.pdf';
        $billing->update();

        return $billing;
    }
}
