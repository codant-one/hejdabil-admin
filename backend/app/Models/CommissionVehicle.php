<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionVehicle extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function commission(): BelongsTo
    {
        return $this->belongsTo(Commission::class, 'commision_id');
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(CarModel::class, 'model_id');
    }

    public function fuel(): BelongsTo
    {
        return $this->belongsTo(Fuel::class, 'fuel_id'); 
    }

    public function gearbox(): BelongsTo
    {
        return $this->belongsTo(Gearbox::class, 'gearbox_id');
    }
}