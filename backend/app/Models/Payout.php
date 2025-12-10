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

        if ($filters->get('user_id') !== null) {
            $query->where('user_id', $filters->get('user_id'));
        } else if(Auth::check() && Auth::user()->getRoleNames()[0] !== 'SuperAdmin' && Auth::user()->getRoleNames()[0] !== 'Administrator') {
            $query->where('user_id', Auth::user()->id);
        }

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
    public static function createPayout($request) {

        $payout = self::create([
            'user_id'                           => Auth::user()->id,
            'payout_state_id'                   => $request->payout_state_id ?? 1,
            'swish_id'                          => $request->swish_id ?? null,
            'reference'                         => $request->reference ?? null,
            'amount'                            => $request->amount ?? 0,
            'payer_alias'                       => $request->payer_alias ?? null,
            'payee_alias'                       => $request->payee_alias ?? null,
            'payee_ssn'                         => $request->payee_ssn ?? null,
            'currency'                          => $request->currency ?? 'SEK',
            'payout_type'                       => $request->payout_type ?? 'PAYOUT',
            'instruction_date'                  => $request->instruction_date ?? null,
            'payout_instruction_uuid'           => $request->payout_instruction_uuid ?? null,
            'message'                           => $request->message ?? null,
            'signing_certificate_serial_number' => $request->signing_certificate_serial_number ?? null,
            'location_url'                      => $request->location_url ?? null,
            'error_message'                     => $request->error_message ?? null,
            'error_code'                        => $request->error_code ?? null
        ]);

        return $payout;
    }    public static function deletePayout($id) {
        self::deletePayouts(array($id));
    }

    public static function deletePayouts($ids) {
        foreach ($ids as $id) {
            $payout = self::find($id);
            $payout->delete();
        }
    }
}

