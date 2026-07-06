<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**** Relationship ****/

    public function featurePlans() {
        return $this->hasMany(FeaturePlan::class, 'feature_id', 'id');
    }
    
}
