<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
    use HasFactory;

    protected $table = 'equipments';
    protected $guarded = [];
    
    /**** Relationship ****/
    public function vehicleEquipmentsList(){
        return $this->hasMany(VehicleEquipmentsList::class, 'equipment_id', 'id');
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
    public static function createEquipment($request) {

        $equipment = self::create([
            'name' => $request->name
        ]);
        
        return $equipment;
    }

    public static function updateEquipment($request, $equipment) {

        $equipment->update([
            'name' => $request->name
        ]);

        return $equipment;
    }

    public static function deleteEquipment($id) {
        self::deleteEquipments(array($id));
    }

    public static function deleteEquipments($ids) {
        foreach ($ids as $id) {
            $equipment = self::find($id);
            $equipment->delete();
        }
    }
}
