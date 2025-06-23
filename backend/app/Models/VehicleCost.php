<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class VehicleCost extends Model
{
    use HasFactory;

    protected $guarded = [];
    
    /**** Relationship ****/
    public function vehicle(){
        return $this->belongsTo(Vehicle::class, 'vehicle_id', 'id');
    }

    public function user(){
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**** Public methods ****/
    public static function createCost($request) {

        $cost = self::create([
            'user_id' => Auth::user()->id,
            'vehicle_id' => $request->vehicle_id,
            'type' => $request->type,
            'description' => $request->description,
            'value' => $request->value,
            'date' => $request->date
        ]);
        
        return $cost;
    }

    public static function updateCost($request, $cost) {

        $cost->update([
            'type' => $request->type,
            'description' => $request->description,
            'value' => $request->value,
            'date' => $request->date
        ]);

        return $cost;
    }

    public static function deleteCost($id) {
        self::deleteCosts(array($id));
    }

    public static function deleteCosts($ids) {
        foreach ($ids as $id) {
            $cost = self::find($id);
            $cost->delete();
        }
    }
}
