<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Payout extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'request_payload' => 'array',
        'response_data' => 'array',
        'amount' => 'decimal:2',
    ];

    protected $appends = ['status_label', 'status_color'];

    // Accessor para el label del status
    public function getStatusLabelAttribute()
    {
        $labels = [
            'CREATED' => 'Creado',
            'DEBITED' => 'Debitado',
            'PAID' => 'Pagado',
            'ERROR' => 'Error',
            'CANCELLED' => 'Cancelado'
        ];
        return $labels[$this->status] ?? $this->status;
    }

    // Accessor para el color del status
    public function getStatusColorAttribute()
    {
        $colors = [
            'CREATED' => 'info',
            'DEBITED' => 'warning',
            'PAID' => 'success',
            'ERROR' => 'error',
            'CANCELLED' => 'secondary'
        ];
        return $colors[$this->status] ?? 'default';
    }

    /**** Relationship ****/
    public function state() {
        return $this->belongsTo(PayoutState::class, 'payout_state_id', 'id');
    }

    public function user(){
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**** Scopes ****/
    public function scopeWhereSearch($query, $search) {
        $query->where(function ($q) use ($search) {
            $q->where('reference', 'LIKE', '%' . $search . '%')
                ->orWhere('amount', 'LIKE', '%' . $search . '%')
                ->orWhere('payer_alias', 'LIKE', '%' . $search . '%');
        });
    }

    public function scopeWhereOrder($query, $orderByField, $orderBy) {
        $query->orderByRaw('(IFNULL('. $orderByField .', id)) '. $orderBy);
    }

    public function scopeApplyFilters($query, array $filters) {
        $filters = collect($filters);

        if ($filters->get('search')) {
            $query->whereSearch($filters->get('search'));
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
    public static function createPayout($request, array $data = []) {

        $payoutData = [
            'user_id'                          => $data['user_id'] ?? ($request->user()->id ?? Auth::user()->id),
            'reference'                        => $data['reference'] ?? $request->reference ?? null,
            'amount'                           => $data['amount'] ?? $request->amount ?? 0,
            'currency'                         => $data['currency'] ?? $request->currency ?? 'SEK',
            'payer_alias'                      => $data['payer_alias'] ?? $request->payer_alias ?? null,
            'payee_alias'                      => $data['payee_alias'] ?? $request->payee_alias ?? null,
            'payee_ssn'                        => $data['payee_ssn'] ?? $request->payee_ssn ?? null,
            'payout_state_id'                  => $data['payout_state_id'] ?? 1,
            'status'                           => $data['status'] ?? 'CREATED',
            'swish_id'                         => $data['swish_id'] ?? null,
            'payout_instruction_uuid'          => $data['payout_instruction_uuid'] ?? null,
            'payer_payment_reference'          => $data['payer_payment_reference'] ?? null,
            'payout_type'                      => $data['payout_type'] ?? 'PAYOUT',
            'instruction_date'                 => $data['instruction_date'] ?? null,
            'message'                          => $data['message'] ?? null,
            'callback_url'                     => $data['callback_url'] ?? null,
            'callback_identifier'              => $data['callback_identifier'] ?? null,
            'signature'                        => $data['signature'] ?? null,
            'signing_certificate_serial_number'=> $data['signing_certificate_serial_number'] ?? null,
            'request_payload'                  => $data['request_payload'] ?? null,
            'response_data'                    => $data['response_data'] ?? null,
            'location_url'                     => $data['location_url'] ?? null,
            'error_message'                    => $data['error_message'] ?? null,
            'error_code'                       => $data['error_code'] ?? null,
        ];

        return self::create($payoutData);
    }

    public static function deletePayout($id) {
        self::deletePayouts(array($id));
    }

    public static function deletePayouts($ids) {
        foreach ($ids as $id) {
            $payout = self::find($id);
            $payout->delete();
        }
    }
}

