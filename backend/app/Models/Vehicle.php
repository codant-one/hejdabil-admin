<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $guarded = [];
    
    /**** Relationship ****/
    public function carbodies(){
        return $this->belongsTo(CarBody::class, 'car_body_id', 'id');
    }  

    public function model(){
        return $this->belongsTo(CarModel::class, 'model_id', 'id');
    }

    public function gearbox(){
        return $this->belongsTo(Gearbox::class, 'gearbox_id', 'id');
    }

    public function iva(){
        return $this->belongsTo(Iva::class, 'iva_id', 'id');
    }

    public function user(){
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function state(){
        return $this->belongsTo(State::class, 'state_id', 'id');
    }

    public function tasks(){
        return $this->hasMany(Task::class, 'vehicle_id', 'id');
    }

    /**** Scopes ****/
    public function scopeWhereSearch($query, $search) {
        $query->where('name', 'LIKE', '%' . $search . '%')
              ->orWhere('url', 'LIKE', '%' . $search . '%');
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
    public static function createVehicle($request) {

        $vehicle = self::create([
            'reg_num' => $request->reg_num
        ]);
        
        return $vehicle;
    }

    public static function updateVehicle($request, $vehicle) {

        $vehicle->update([
            'mileage' => $request->mileage ?? null,
            'generation' => $request->generation ?? null,
            'year' => $request->year ?? null,
            'first_insc' => $request->first_insc ?? null,
            'purchase_price' => $request->purchase_price ?? null,
            'purchase_date' => $request->purchase_date ?? null,
            'sale_price' => $request->sale_price ?? null,
            'sale_date' => $request->sale_date ?? null,
            'number_keys' => $request->number_keys ?? null,
            'service_book' => $request->service_book ?? null,
            'summer_tire' => $request->summer_tire ?? null,
            'winter_tire' => $request->winter_tire ?? null,
            'last_service' => $request->last_service ?? null,
            'dist_belt' => $request->dist_belt ?? null,
            'last_dist_belt' => $request->last_dist_belt ?? null,
            'comments' => $request->comments ?? null
        ]);

        return $vehicle;
    }

}
