<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Iva extends Model
{
    use HasFactory;

    protected $guarded = [];
    
    /**** Relationship ****/
    public function vehicles(){
        return $this->hasMany(Vehicle::class, 'iva_id', 'id');
    }

    public function agreement(){
        return $this->hasMany(Agreement::class, 'iva_id', 'id');
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
    public static function createIva($request) {

        $iva = self::create([
            'name' => $request->name
        ]);
        
        return $iva;
    }

    public static function updateIva($request, $iva) {

        $iva->update([
            'name' => $request->name
        ]);

        return $iva;
    }

    public static function deleteIva($id) {
        self::deleteIvas(array($id));
    }

    public static function deleteIvas($ids) {
        foreach ($ids as $id) {
            $iva = self::find($id);
            $iva->delete();
        }
    }
}
