<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Country extends Model
{
    use HasFactory;

    protected $guarded = [];
    
    /**** Relationship ****/
    public function state() {
        return $this->belongsTo(State::class, 'state_id', 'id');
    }

    /**** Public methods ****/
    public static function forDropdown()
    {
        return DB::table('countries as c')
            ->select(['c.id', 'c.name'])
            ->orderBy('name')
            ->get()->pluck('name','id');
    }

    public static function forDropdownByID($id)
    {
        return DB::table('countries as c')
            ->select(['c.id', 'c.name'])
            ->where('id', $id)
            ->orderBy('name')
            ->get()->pluck('name','id');
    }
    

    /**
     * forDropdownByState: Get registers by states
     * 
     * @param Array  states
     */
    public static function forDropdownByStates($states)
    {
        return DB::table('countries as c')
            ->select(['c.id', 'c.name'])
            ->whereIn('c.state_id', $states)
            ->orderBy('name')
            ->get()->pluck('name','id');
        
    }

}
