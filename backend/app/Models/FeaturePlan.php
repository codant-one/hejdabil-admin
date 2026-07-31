<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeaturePlan extends Model
{
    protected $guarded = [];

    /**** Relationship ****/
    public function feature()
    {
        return $this->belongsTo(Feature::class, 'feature_id', 'id');
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id', 'id');
    }
}
