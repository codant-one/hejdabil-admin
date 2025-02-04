<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $appends = ['full_name'];

    /**** Relationship ****/
    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function clients() {
        return $this->hasMany(Client::class, 'supplier_id', 'id');
    }

    /**** Scopes ****/
    public function scopeClientsCount($query)
    {
        return  $query->addSelect(['client_count' => function ($q){
                    $q->selectRaw('COUNT(*)')
                        ->from('suppliers as s')
                        ->join('clients as c', 'c.supplier_id', '=', 's.id')
                        ->whereColumn('s.id', 'suppliers.id');
                }]);
    }

    public function scopeWhereSearch($query, $search) {
        $query->whereHas('user', function ($q) use ($search) {
            $q->where(function ($query) use ($search) {
                $query->where('name', 'LIKE', '%' . $search . '%')
                      ->orWhere('last_name', 'LIKE', '%' . $search . '%')
                      ->orWhere('email', 'LIKE', '%' . $search . '%');
            });
        })->orWhere('company', 'LIKE', '%' . $search . '%');
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
     public static function createSupplier($request) {
        $user = User::createUser($request);
        $user->assignRole('Supplier');

        $supplier = self::create([
            'user_id' => $user->id,
            'company' => $request->company,
            'organization_number' => $request->organization_number,
            'link' => $request->link,
            'address' => $request->address,
            'street' => $request->street,
            'postal_code' => $request->postal_code,
            'phone' => $request->phone,
            'bank' => $request->bank,
            'account_number' => $request->account_number
        ]);

        return $supplier;
    }

    public static function updateSupplier($request, $supplier) {

        $user = User::with('userDetail')->find($supplier->user_id);

        $supplier->update([
            'company' => $request->company,
            'organization_number' => $request->organization_number,
            'link' => $request->link,
            'address' => $request->address,
            'street' => $request->street,
            'postal_code' => $request->postal_code,
            'phone' => $request->phone,
            'bank' => $request->bank,
            'account_number' => $request->account_number
        ]);

        User::updateUser($request, $user);
        
        $user->assignRole('Supplier');

        return $supplier;
    }

    public static function deleteSupplier($id) {
        self::deleteSuppliers(array($id));
    }

    public static function deleteSuppliers($ids) {
        foreach ($ids as $id) {
            $supplier = self::find($id);
            $user = User::find($supplier->user_id);

            if($supplier->logo)
                deleteFile($supplier->logo);
            
            $supplier->delete();
            
            User::deleteUser($user->id);
        }
    }

    public static function updateOrCreateSupplier($request, $user) {
        $supplier = Supplier::updateOrCreate(
            [    'user_id' => $user->id ],
            [
                'company' => $request->company,
                'organization_number' => $request->organization_number,
                'link' => $request->link,
                'address' => $request->address,
                'street' => $request->street,
                'postal_code' => $request->postal_code,
                'phone' => $request->phone,
                'bank' => $request->bank,
                'account_number' => $request->account_number,
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
            ]
        );

        return $supplier;
    }

    /**** attributes ****/
    public function getFullNameAttribute()
    {
        return "{$this->user->name} {$this->user->last_name}";
    }

}
