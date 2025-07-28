<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Identification extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**** Relationship ****/
    public function vehicle_client(){
        return $this->hasMany(VehicleClient::class, 'identification_id', 'id');
    }

    public function commission_client(){
        return $this->hasMany(CommissionClient::class, 'identification_id', 'id');
    }

    public function agreement_client(){
        return $this->hasMany(VehicleClient::class, 'identification_id', 'id');
    }
}
