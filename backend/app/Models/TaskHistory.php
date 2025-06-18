<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TaskHistory extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**** Relationship ****/
    public function task(){
        return $this->belongsTo(VehicleTask::class, 'vehicle_task_id', 'id');
    }

    public function user(){
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
