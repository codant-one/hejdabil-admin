<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];
    protected $appends = ['full_name'];

    /**** Relationship ****/
    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id')->withTrashed();
    }

    public function clients() {
        return $this->hasMany(Client::class, 'supplier_id', 'id');
    }

    public function billings() {
        return $this->hasMany(Billing::class, 'supplier_id', 'id');
    }

    public function state() {
        return $this->belongsTo(State::class, 'state_id', 'id');
    }

    public function agreements(){
        return $this->hasMany(Agreement::class, 'supplier_id', 'id');
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
     public static function createSupplier($request) {
        $user = User::createUser($request);
        $user->assignRole('Supplier');

        $supplier = self::create([
            'user_id' => $user->id,
        ]);

        $user_details = UserDetails::where('user_id', $user->id)->first();
        $user_details->updateOrCreateUser($request, $user);

        return $supplier;
    }

    public static function updateSupplier($request, $supplier) {

        $user = User::with('userDetail')->find($supplier->user_id);

        User::updateUser($request, $user);
        
        $user->assignRole('Supplier');
        $user_details = UserDetails::where('user_id', $user->id)->first();
        $user_details->updateOrCreateUser($request, $user);

        return $supplier;
    }

    public static function deleteSupplier($id) {
        self::deleteSuppliers(array($id));
    }

    public static function deleteSuppliers($ids) {
        foreach ($ids as $id) {
            $supplier = self::find($id);
            $supplier->state_id = 1;
            $supplier->save();

            $user = User::find($supplier->user_id);

            if($supplier->logo)
                deleteFile($supplier->logo);
            
            $supplier->delete();
            
            User::deleteUser($user->id);
        }
    }

    public static function activateSupplier($id) {
        $supplier = self::onlyTrashed()->where('id', $id)->first();
        $supplier->restore();
        $supplier->state_id = 2;
        $supplier->save();

        $user = User::onlyTrashed()->where('id', $supplier->user_id)->first();
        $user->restore();
        $user->assignRole('Supplier');
    }

    /**** attributes ****/
    public function getFullNameAttribute()
    {
        if ($this->user)
            return "{$this->user->name} {$this->user->last_name} - {$this->user->userDetail->company}";
        else
            return "";
    }

}
