<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fuel extends Model
{
    use HasFactory;

    protected $guarded = [];
    
    /**** Relationship ****/
    public function vehicles(){
        return $this->hasMany(Vehicle::class, 'fuel_id', 'id');
    }

    public function commission_vehicles(){
        return $this->hasMany(CommissionVehicle::class, 'fuel_id', 'id');
    }
}
