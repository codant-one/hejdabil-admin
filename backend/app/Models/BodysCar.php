<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BodysCar extends Model
{
    use HasFactory;

    protected $table = 'bodys_car';

    protected $guarded = [];
    
    /**** Relationship ****/
    public function vehicles(){
        return $this->hasMany(Vehicle::class, 'bodys_car_id', 'id');
    }

    /**** Scopes ****/
    public function scopeWhereSearch($query, $search) {
        $query->where('name', 'LIKE', '%' . $search . '%');
    }

    public function scopeWhereOrder($query, $orderByField, $orderBy) {
        $query->orderByRaw('(IFNULL('. $orderByField .', id)) '. $orderBy);
    }

    public function scopeApplyFilters($query, array $filters) {
        $filters = collect($filters);

        if ($filters->get('search')) {
            $query->whereSearch($filters->get('search'));
        }

        if ($filters->get('orderByField') || $filters->get('orderBy')) {
            $field = $filters->get('orderByField') ? $filters->get('orderByField') : 'order_id';
            $orderBy = $filters->get('orderBy') ? $filters->get('orderBy') : 'asc';
            $query->whereOrder($field, $orderBy);
        }
    }

    public function scopePaginateData($query, $limit) {
        if ($limit == 'all') {
            return collect(['data' => $query->get()]);
        }

        return $query->paginate($limit);
    }

    /**** Public methods ****/
    public static function createObject($request) {

        $object = self::create([
            'name' => $request->name
        ]);
        
        return $object;
    }

    public static function updateObject($request, $object) {

        $object->update([
            'name' => $request->name
        ]);

        return $object;
    }

    public static function deleteObject($id) {
        self::deleteObjects(array($id));
    }

    public static function deleteObjects($ids) {
        foreach ($ids as $id) {
            $object = self::find($id);
            $object->delete();
        }
    }
}
