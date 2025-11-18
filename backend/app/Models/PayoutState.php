<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PayoutState extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**** Public methods ****/
    public static function forDropdown()
    {
        return DB::table('payout_states as s')
            ->select(['s.id', 's.name' ])
            ->get()->pluck('name','id');
    }
}
