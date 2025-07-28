<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Commission extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    public function vehicle(): HasOne
    {       
        return $this->hasOne(CommissionVehicle::class, 'commision_id');
    }

    public function client(): HasOne
    {       
        return $this->hasOne(CommissionClient::class, 'commision_id');
    }

    public function commission_type() {
        return $this->belongsTo(CommissionType::class, 'commision_type_id', 'id');
    }
}