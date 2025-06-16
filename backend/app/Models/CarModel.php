<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarModel extends Model
{
    use HasFactory;

    protected $table = 'models';

     protected $guarded = [];
    
    /**** Relationship ****/
    public function brand(){
        return $this->belongsTo(Brand::class, 'brand_id', 'id');
    } 

    public function vehicles(){
        return $this->hasMany(Vehicle::class, 'model_id', 'id');
    }

    /**** Scopes ****/
    public function scopeWhereSearch($query, $search) {
        $query->whereHas('brand', function ($q) use ($search) {
            $q->where(function ($query) use ($search) {
                $query->where('name', 'LIKE', '%' . $search . '%')
                      ->orWhere('url', 'LIKE', '%' . $search . '%');
            });
        })->orWhere('name', 'LIKE', '%' . $search . '%');
    }

    public function scopeWhereOrder($query, $orderByField, $orderBy) {
        $query->orderByRaw('(IFNULL('. $orderByField .', id)) '. $orderBy);
    }

    public function scopeApplyFilters($query, array $filters) {
        $filters = collect($filters);

        if ($filters->get('search')) {
            $query->whereSearch($filters->get('search'));
        }

        if ($filters->get('brand_id') !== null) {
            $query->where('brand_id', $filters->get('brand_id'));
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
    public static function createModel($request) {

        $model = self::create([
            'name' => $request->name,
            'brand_id' => $request->brand_id
        ]);
        
        return $model;
    }

    public static function updateModel($request, $model) {

        $model->update([
            'name' => $request->name,
            'brand_id' => $request->brand_id
        ]);

        return $model;
    }

    public static function deleteModel($id) {
        self::deleteModels(array($id));
    }

    public static function deleteModels($ids) {
        foreach ($ids as $id) {
            $model = self::find($id);
            $model->delete();
        }
    }
}
