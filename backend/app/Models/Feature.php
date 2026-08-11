<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use App\Models\Plan;

class Feature extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**** Relationship ****/
    public function featurePlans() {
        return $this->hasMany(FeaturePlan::class, 'feature_id', 'id');
    }

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class, 'feature_plans', 'feature_id', 'plan_id');
    }
    
}
