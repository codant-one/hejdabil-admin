<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
    use HasFactory;

    protected $guarded = [];

    public static function getByKey($key)
    {
        return static::where('key', 'featured_'.$key)->first();
    }

}
