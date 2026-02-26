<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class VehicleClient extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**** Relationship ****/
    public function client(){
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }

    public function vehicle(){
        return $this->belongsTo(Vehicle::class, 'vehicle_id', 'id');
    }

    public function identification(){
        return $this->belongsTo(Identification::class, 'identification_id', 'id');
    }

    public function client_type(){
        return $this->belongsTo(ClientType::class, 'client_type_id', 'id');
    }

    public function country(){
        return $this->belongsTo(country::class, 'country_id', 'id');
    }

    public function agreement(){
        return $this->hasOne(Agreement::class, 'vehicle_client_id', 'id');
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
    public static function createClient($request) {

        $client = self::create([
            'vehicle_id' => $request->vehicle_id === 'null' ? null : $request->vehicle_id,
            'client_type_id' => $request->client_type_id === 'null' ? null : $request->client_type_id,
            'country_id' => $request->country_id === 'null' ? null : $request->country_id,
            'identification_id' => $request->identification_id === 'null' ? null : $request->identification_id,
            'client_id' => $request->client_id === 'null' ? null : $request->client_id,
            'type' => $request->type ?? 1,
            'fullname' => $request->fullname === 'null' ? null : $request->fullname,
            'email' => $request->email === 'null' ? null : $request->email,
            'organization_number' => $request->organization_number === 'null' ? null : $request->organization_number,
            'address' => $request->address === 'null' ? null : $request->address,
            'postal_code' => $request->postal_code === 'null' ? null : $request->postal_code,
            'phone' => $request->phone === 'null' ? null : $request->phone,
            'street' => $request->street === 'null' ? null : $request->street
        ]);

        return $client;
    }

    public static function updateClient($request, $client) {
        $client->update([
            'client_type_id' => $request->client_type_id === 'null' ? null : $request->client_type_id,
            'country_id' => $request->country_id === 'null' ? null : $request->country_id,
            'identification_id' => $request->identification_id === 'null' ? null : $request->identification_id,
            'client_id' => $request->client_id === 'null' ? null : $request->client_id,
            'type' => $request->type ?? 1,
            'fullname' => $request->fullname === 'null' ? null : $request->fullname,
            'email' => $request->email === 'null' ? null : $request->email,
            'organization_number' => $request->organization_number === 'null' ? null : $request->organization_number,
            'address' => $request->address === 'null' ? null : $request->address,
            'postal_code' => $request->postal_code === 'null' ? null : $request->postal_code,
            'phone' => $request->phone === 'null' ? null : $request->phone,
            'street' => $request->street === 'null' ? null : $request->street
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
