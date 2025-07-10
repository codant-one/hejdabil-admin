<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentType extends Model
{
    use HasFactory;

    /**** Relationship ****/
    public function agreement(){
        return $this->hasMany(Agreement::class, 'payment_type_id', 'id');
    }
}
