<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceCompany extends Model
{
    use HasFactory;

    /**** Relationship ****/
    public function agreement(){
        return $this->hasMany(Agreement::class, 'insurance_company_id', 'id');
    }
}
}
