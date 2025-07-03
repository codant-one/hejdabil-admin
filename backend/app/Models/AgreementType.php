<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgreementType extends Model
{
    use HasFactory;

    public function agreement(){
        return $this->hasMany(Agreement::class, 'agreement_type_id', 'id');
    }
}
