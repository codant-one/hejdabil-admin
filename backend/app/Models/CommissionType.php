<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CommissionType extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**** Relationship ****/
    public function commission() {
        return $this->hasMany(Commission::class, 'commission_type_id', 'id');
    }

}