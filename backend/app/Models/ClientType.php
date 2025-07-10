<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ClientType extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**** Relationship ****/
    public function vehicle_client(){
        return $this->hasMany(VehicleClient::class, 'client_type_id', 'id');
    }

    public function agreement_client(){
        return $this->hasMany(VehicleClient::class, 'client_type_id', 'id');
    }

    /**** Public methods ****/
    public static function forDropdown()
    {
        return DB::table('client_types as t')
            ->select(['t.id', 't.name' ])
            ->get()->pluck('name','id');
    }
}
