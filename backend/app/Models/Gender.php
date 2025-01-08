<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gender extends Model
{
    use HasFactory;

    /**** Relationship ****/
    
    /**** Public methods ****/
    public static function forDropdown()
    {
        return DB::table('genders as g')
                 ->select(['g.id', 'g.name' ])
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
        return DB::table('genders as g')
            ->select(['g.id', 'g.name'])
            ->whereIn('g.state_id', $states)
            ->orderBy('name')
            ->get()->pluck('name','id');
        
    }
}
