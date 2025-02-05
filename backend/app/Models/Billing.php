<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\SoftDeletes;

class Billing extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    /**** Relationship ****/
    public function supplier() {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }

    public function client() {
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }

    public function state() {
        return $this->belongsTo(State::class, 'state_id', 'id');
    }

    /**** Scopes ****/
    public function scopeWhereSearch($query, $search) {
        $query->whereHas('client', function ($q) use ($search) {
            $q->where(function ($query) use ($search) {
                $query->where('fullname', 'LIKE', '%' . $search . '%');
            });
        })->orWhereHas('supplier', function ($q) use ($search) {
            $q->whereHas('user', function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('name', 'LIKE', '%' . $search . '%')
                            ->orWhere('last_name', 'LIKE', '%' . $search . '%')
                            ->orWhere('email', 'LIKE', '%' . $search . '%');
                });
            });
        });
    }

    public function scopeWhereOrder($query, $orderByField, $orderBy) {
        $query->orderByRaw('(IFNULL('. $orderByField .', id)) '. $orderBy);
    }

    public function scopeApplyFilters($query, array $filters) {
        $filters = collect($filters);

        if(Auth::check() && Auth::user()->getRoleNames()[0] === 'Supplier') {
            $query->where('supplier_id', Auth::user()->supplier->id);
        }

        if ($filters->get('search')) {
            $query->whereSearch($filters->get('search'));
        }

        if ($filters->get('supplier_id') !== null) {
            $query->where('supplier_id', $filters->get('supplier_id'));
        }

        if ($filters->get('client_id') !== null) {
            $query->where('client_id', $filters->get('client_id'));
        }

        if ($filters->get('state_id') !== null) {
            $query->where('state_id', $filters->get('state_id'));
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
    public static function createBilling($request) {

        $details = [];

        foreach($request->details as $item) {
            $array = [];
            foreach(json_decode($item, true) as $key => $value) {
                $aux = [];
                $aux['id'] = $key;
                $aux['value'] = $key === 3 || $key === 4 ? number_format($value, 2, '.', '') : $value;

                array_push($array, $aux);
            }
            array_push($details, $array);
        }

        $isSupplier = Auth::check() && Auth::user()->getRoleNames()[0] === 'Supplier';

        $billing = self::create([
            'supplier_id' => $request->supplier_id === 'null' ? ($isSupplier ? Auth::user()->supplier->id : null) : $request->supplier_id,
            'client_id' =>  $request->client_id,
            'invoice_id' =>  $request->invoice_id,
            'invoice_date' =>  $request->invoice_date,
            'due_date' =>  $request->due_date,
            'payment_terms' =>  $request->payment_terms . ' days net',
            'reference' => $request->reference === 'null' ? null : $request->reference,
            'subtotal' =>  $request->subtotal,
            'tax' =>  $request->tax,
            'total' =>  $request->total,
            'detail' => json_encode($details, true)
        ]);
        
        return $billing;
    }

    public static function updateBilling($request, $billing) {

        $billing->update([
            'type_id' => $request->type_id,
            'name_en' => $request->name_en,
            'name_se' => $request->name_se,
            'description_en' => $request->description_en === 'null' ? null : $request->description_en,
            'description_se' => $request->description_se === 'null' ? null : $request->description_se
        ]);

        return $billing;
    }

    public static function deleteBilling($id) {
        self::deleteBillings(array($id));
    }

    public static function deleteBillings($ids) {
        foreach ($ids as $id) {
            $billing = self::find($id);
            $billing->delete();
        }
    }
}
