<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Type extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $appends = ['name'];

    /**** attributes ****/
    public function getNameAttribute()
    {
        return "{$this->name_en} / {$this->name_se}";
    }
}
