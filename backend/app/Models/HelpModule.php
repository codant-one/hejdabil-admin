<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HelpModule extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'label', 'icon'];

    /** @return HasMany<HelpVideo> */
    public function videos(): HasMany
    {
        return $this->hasMany(HelpVideo::class);
    }
}
