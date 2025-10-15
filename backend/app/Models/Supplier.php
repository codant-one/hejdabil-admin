<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];
    protected $appends = ['full_name'];

    /**** Relationship ****/
    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id')->withTrashed();
    }

    public function creator() {
        return $this->belongsTo(User::class, 'creator_id', 'id')->withTrashed();
    }

    public function clients() {
        return $this->hasMany(Client::class, 'supplier_id', 'id');
    }

    public function boss() {
        return $this->belongsTo(Supplier::class, 'boss_id', 'id');
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
        $query->where(function ($q) use ($search) {
            $q->whereHas('user', function ($uq) use ($search) {
                $uq->where(function ($inner) use ($search) {
                    $inner->where('name', 'LIKE', '%' . $search . '%')
                         ->orWhere('last_name', 'LIKE', '%' . $search . '%')
                         ->orWhere('email', 'LIKE', '%' . $search . '%')
                         ->orWhereRaw("CONCAT(name, ' ', last_name) LIKE ?", ['%' . $search . '%']);
                });
            })
            ->orWhereHas('user.userDetail', function ($dq) use ($search) {
                $dq->where('company', 'LIKE', '%' . $search . '%')
                   ->orWhere('organization_number', 'LIKE', '%' . $search . '%');
            })
            ->orWhereHas('creator', function ($uq) use ($search) {
                $uq->where(function ($inner) use ($search) {
                    $inner->where('name', 'LIKE', '%' . $search . '%')
                         ->orWhere('last_name', 'LIKE', '%' . $search . '%')
                         ->orWhere('email', 'LIKE', '%' . $search . '%')
                         ->orWhereRaw("CONCAT(name, ' ', last_name) LIKE ?", ['%' . $search . '%']);
                });
            });
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

        if( $request->has('boss_id') )
            $user->assignRole('User');
        else
            $user->assignRole('Supplier');

        $supplier = self::create([
            'user_id' => $user->id,
            'creator_id' => Auth::user()->id,
            'boss_id' => ( $request->has('boss_id') ) ? $request->boss_id : null,
            'order_id' => ( $request->has('order_id') ) ? $request->order_id : null
        ]);

        $user_details = UserDetails::where('user_id', $user->id)->first();
        $user_details->updateOrCreateUser($request, $user);

        return $supplier;
    }

    public static function updateSupplier($request, $supplier) {

        $user = User::with('userDetail')->find($supplier->user_id);

        User::updateUser($request, $user);
        
        if( $supplier->boss_id > 0 )
            $user->assignRole('User');
        else
            $user->assignRole('Supplier');

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

    public static function createUserRelatedToSupplier($request) {
        $user = User::createUser($request);
        $user->assignRole('User');

        $supplier = self::create([
            'user_id' => $user->id,
            'boss_id' => $request->boss_id,
            'company' => $request->company,
            'organization_number' => $request->organization_number,
            'link' => $request->link,
            'address' => $request->address,
            'street' => $request->street,
            'postal_code' => $request->postal_code,
            'phone' => $request->phone,
            'bank' => $request->bank,
            'account_number' => $request->account_number,
            'swish' => $request->swish === 'null' ? null : $request->swish
        ]);

        return $supplier;
    }

    public static function updateUserRelatedToSupplier($request, $supplier) {

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
            'account_number' => $request->account_number,
            'swish' => $request->swish === 'null' ? null : $request->swish
        ]);

        User::updateUser($request, $user);
        
        $user->assignRole('User');

        return $supplier;
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
