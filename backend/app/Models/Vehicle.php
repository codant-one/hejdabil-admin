<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $guarded = [];
    
    /**** Relationship ****/
    public function bodyscar(){
        return $this->belongsTo(CarBody::class, 'car_body_id', 'id');
    }  

    public function model(){
        return $this->belongsTo(CarModel::class, 'model_id', 'id');
    }

    public function vehicleEquipmentsList(){
        return $this->belongsTo(VehicleEquipmentsList::class, 'equipment_id', 'id');
    }

    public function gearbox(){
        return $this->belongsTo(Gearbox::class, 'gearbox_id', 'id');
    }

    public function iva(){
        return $this->belongsTo(Iva::class, 'iva_id', 'id');
    }

    public function user(){
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function state(){
        return $this->belongsTo(State::class, 'state_id', 'id');
    }

    public function tasks(){
        return $this->hasMany(Task::class, 'vehicle_id', 'id');
    }
}
