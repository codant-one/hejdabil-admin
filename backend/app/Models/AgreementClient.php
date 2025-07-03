<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgreementClient extends Model
{
    use HasFactory;

    /**** Relationship ****/
    public function supplier() {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }

    public function agreement(){
        return $this->hasMany(Agreement::class, 'agreement_client_id', 'id');
    }


    /**** Scopes ****/
    public function scopeWhereSearch($query, $search) {
        $query->where('fullname', 'LIKE', '%' . $search . '%')
              ->orWhere('email', 'LIKE', '%' . $search . '%');
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
            'organization_number' => $request->organization_number === 'null' ? null : $request->organization_number,
            'address' => $request->address,
            'street' => $request->street,
            'postal_code' => $request->postal_code,
            'phone' => $request->phone,
            'reference' => $request->reference === 'null' ? null : $request->reference
        ]);
        
        return $client;
    }

    public static function updateClient($request, $client) {
        $isSupplier = Auth::user()->getRoleNames()[0] === 'Supplier';

        $client->update([
            'supplier_id' => $request->supplier_id === 'null' && $isSupplier ? Auth::user()->supplier->id : ($request->supplier_id === 'null' ? null : $request->supplier_id),
            'email' => $request->email,
            'fullname' => $request->fullname,
            'organization_number' => $request->organization_number === 'null' ? null : $request->organization_number,
            'address' => $request->address,
            'street' => $request->street,
            'postal_code' => $request->postal_code,
            'phone' => $request->phone,
            'reference' => $request->reference === 'null' ? null : $request->reference
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
