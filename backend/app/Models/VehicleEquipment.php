<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleEquipment extends Model
{
    use HasFactory;

    protected $guarded = [];
    
    /**** Relationship ****/
    public function equipment(){
        return $this->belongsTo(Equipment::class, 'equipment_id', 'id');
    }

    public function vehicles(){
        return $this->hasMany(Vehicle::class, 'equipment_id', 'id');
    }
}
