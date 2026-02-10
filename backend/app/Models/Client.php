<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    /**** Relationship ****/
    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id')->withTrashed();
    }
    
    public function supplier() {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }

    public function agreement_client(){
        return $this->hasMany(AgreementClient::class, 'client_id', 'id');
    }

    public function vehicle_client(){
        return $this->hasMany(VehicleClient::class, 'client_id', 'id');
    }

    public function state() {
        return $this->belongsTo(State::class, 'state_id', 'id');
    }

    /**** Scopes ****/
    public function scopeWhereSearch($query, $search) {
        $query->where(function ($q) use ($search) {
            $q->where('fullname', 'LIKE', '%' . $search . '%')
                ->orWhere('organization_number', 'LIKE', '%' . $search . '%')
                ->orWhere('phone', 'LIKE', '%' . $search . '%')
                ->orWhere('address', 'LIKE', '%' . $search . '%')
                ->orWhere('email', 'LIKE', '%' . $search . '%')
                ->orWhereHas('user', function ($uq) use ($search) {
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
    public static function createClient($request) {

        $isSupplier = Auth::user()->getRoleNames()[0] === 'Supplier';
        $isUser = Auth::user()->getRoleNames()[0] === 'User';
        $supplier_id = match (true) {
            $request->supplier_id === 'null' && $isSupplier => Auth::user()->supplier->id,
            $isUser => Auth::user()->supplier->boss_id,
            $request->supplier_id === 'null' => null,
            default => $request->supplier_id,
        };

        $client = self::create([
            'user_id' => Auth::user()->id, 
            'supplier_id' =>  $supplier_id,
            'email' => $request->email,
            'fullname' => $request->fullname,
            'organization_number' => $request->organization_number === 'null' ? null : $request->organization_number,
            'address' => $request->address,
            'street' => $request->street,
            'postal_code' => $request->postal_code,
            'phone' => $request->phone,
            'reference' => $request->reference === 'null' ? null : $request->reference,
            'comments' =>  $request->comments === 'null' ? null : $request->comments
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
            'reference' => $request->reference === 'null' ? null : $request->reference,
            'comments' =>  $request->comments === 'null' ? null : $request->comments
        ]);

        return $client;
    }

    public static function deleteClient($id) {
        self::deleteClients(array($id));
    }

    public static function deleteClients($ids) {
        foreach ($ids as $id) {
            $client = self::find($id);
            $client->state_id = 1;
            $client->save();
            
            $client->delete();
        }
    }

    public static function activateClient($id) {
        $client = self::onlyTrashed()->where('id', $id)->first();
        $client->restore();
        $client->state_id = 2;
        $client->save();
    }
}

