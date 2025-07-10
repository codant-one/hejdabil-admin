<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleInterchange extends Model
{
    use HasFactory;

    /**** Relationship ****/
    public function agreement(){
        return $this->hasMany(Agreement::class, 'vehicle_interchange_id', 'id');
    }
}
