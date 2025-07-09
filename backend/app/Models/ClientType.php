<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ClientType extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**** Public methods ****/
    public static function forDropdown()
    {
        return DB::table('client_types as t')
            ->select(['t.id', 't.name' ])
            ->get()->pluck('name','id');
    }
}
