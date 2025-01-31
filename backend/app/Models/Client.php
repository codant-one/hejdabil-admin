<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Client extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**** Relationship ****/
    public function supplier() {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }

    /**** Scopes ****/
    public function scopeWhereSearch($query, $search) {
        $query->where('fullname', 'LIKE', '%' . $search . '%')
              ->orWhere('email', 'LIKE', '%' . $search . '%')
              ->orWhere('company', 'LIKE', '%' . $search . '%');
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
    public static function createClient($request) {

        $isSupplier = Auth::user()->getRoleNames()[0] === 'Supplier';

        $client = self::create([
            'supplier_id' => $request->supplier_id === 'null' && $isSupplier ? Auth::user()->supplier->id : ($request->supplier_id === 'null' ? null : $request->supplier_id),
            'email' => $request->email,
            'fullname' => $request->fullname,
            'company' => $request->company === 'null' ? null : $request->company,
            'organization_number' => $request->organization_number === 'null' ? null : $request->organization_number,
            'link' => $request->link === 'null' ? null : $request->link,
            'address' => $request->address,
            'street' => $request->street,
            'postal_code' => $request->postal_code,
            'phone' => $request->phone,
            'bank' => $request->bank,
            'account_number' => $request->account_number,
            'reference' => $request->reference,
            'iban' => $request->iban === 'null' ? null : $request->iban,
            'compensation_number' => $request->compensation_number === 'null' ? null : $request->compensation_number,
            'iban_number' => $request->iban_number === 'null' ? null : $request->iban_number,
            'bic' => $request->bic === 'null' ? null : $request->bic,
            'bank_transfer' => $request->bank_transfer === 'null' ? null : $request->bank_transfer,
            'plus_spin' => $request->plus_spin === 'null' ? null : $request->plus_spin,
            'whistle' => $request->whistle === 'null' ? null : $request->whistle,
            'registration_fee' => $request->registration_fee === 'null' ? null : $request->registration_fee,
            'insurance_company' => $request->insurance_company === 'null' ? null : $request->insurance_company,
            'financial_company' => $request->financial_company === 'null' ? null : $request->financial_company,
            'interest' => $request->interest === 'null' ? null : $request->interest,
            'avi_fee' => $request->avi_fee === 'null' ? null : $request->avi_fee,
            'installation_fee' => $request->installation_fee === 'null' ? null : $request->installation_fee
        ]);
        
        return $client;
    }

    public static function updateClient($request, $client) {
        $isSupplier = Auth::user()->getRoleNames()[0] === 'Supplier';

        $client->update([
            'supplier_id' => $request->supplier_id === 'null' && $isSupplier ? Auth::user()->supplier->id : ($request->supplier_id === 'null' ? null : $request->supplier_id),
            'email' => $request->email,
            'fullname' => $request->fullname,
            'company' => $request->company === 'null' ? null : $request->company,
            'organization_number' => $request->organization_number === 'null' ? null : $request->organization_number,
            'link' => $request->link === 'null' ? null : $request->link,
            'address' => $request->address,
            'street' => $request->street,
            'postal_code' => $request->postal_code,
            'phone' => $request->phone,
            'bank' => $request->bank,
            'account_number' => $request->account_number,
            'reference' => $request->reference,
            'iban' => $request->iban === 'null' ? null : $request->iban,
            'compensation_number' => $request->compensation_number === 'null' ? null : $request->compensation_number,
            'iban_number' => $request->iban_number === 'null' ? null : $request->iban_number,
            'bic' => $request->bic === 'null' ? null : $request->bic,
            'bank_transfer' => $request->bank_transfer === 'null' ? null : $request->bank_transfer,
            'plus_spin' => $request->plus_spin === 'null' ? null : $request->plus_spin,
            'whistle' => $request->whistle === 'null' ? null : $request->whistle,
            'registration_fee' => $request->registration_fee === 'null' ? null : $request->registration_fee,
            'insurance_company' => $request->insurance_company === 'null' ? null : $request->insurance_company,
            'financial_company' => $request->financial_company === 'null' ? null : $request->financial_company,
            'interest' => $request->interest === 'null' ? null : $request->interest,
            'avi_fee' => $request->avi_fee === 'null' ? null : $request->avi_fee,
            'installation_fee' => $request->installation_fee === 'null' ? null : $request->installation_fee
        ]);

        return $client;
    }

    public static function deleteClient($id) {
        self::deleteClients(array($id));
    }

    public static function deleteClients($ids) {
        foreach ($ids as $id) {
            $client = self::find($id);
            $client->delete();
        }
    }
}

