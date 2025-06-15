<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleEquipmentsList extends Model
{
    use HasFactory;

    protected $table = 'vehicle_equipments_list';

    protected $guarded = [];
    
    /**** Relationship ****/
    public function equipmentsList(){
        return $this->belongsTo(EquipmentsList::class, 'equipment_id', 'id');
    }

    public function vehicles(){
        return $this->hasMany(Vehicle::class, 'equipment_id', 'id');
    }
}
