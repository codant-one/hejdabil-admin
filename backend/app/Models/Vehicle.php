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
        return $this->hasMany(VehicleTask::class, 'vehicle_id', 'id');
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
            'model_id' => $request->model_id === 'null' ? null : $request->model_id,
            'car_body_id' => $request->car_body_id === 'null' ? null : $request->car_body_id,
            'gearbox_id' => $request->gearbox_id === 'null' ? null : $request->gearbox_id,
            'iva_id' => $request->iva_id === 'null' ? null : $request->iva_id,
            'state_id' => $request->state_id === 'null' ? null : $request->state_id,
            'mileage' => $request->mileage === 'null' ? null : $request->mileage,
            'generation' => $request->generation === 'null' ? null : $request->generation,
            'year' => $request->year === 'null' ? null : $request->year,
            'first_insc' => $request->first_insc === 'null' ? null : $request->first_insc,
            'control_inspection' => $request->control_inspection === 'null' ? null : $request->control_inspection,
            'color' => $request->color === 'null' ? null : $request->color,
            'fuel' => $request->fuel === 'null' ? null : $request->fuel,
            'purchase_price' => $request->purchase_price === 'null' ? null : $request->purchase_price,
            'purchase_date' => $request->purchase_date === 'null' ? null : $request->purchase_date,
            'sale_price' => $request->sale_price === 'null' ? null : $request->sale_price,
            'min_sale_price' => $request->min_sale_price === 'null' ? null : $request->min_sale_price,
            'sale_date' => $request->sale_date === 'null' ? null : $request->sale_date,
            'number_keys' => $request->number_keys === 'null' ? null : $request->number_keys,
            'service_book' => $request->service_book === 'null' ? null : $request->service_book,
            'summer_tire' => $request->summer_tire === 'null' ? null : $request->summer_tire,
            'winter_tire' => $request->winter_tire === 'null' ? null : $request->winter_tire,
            'last_service' => $request->last_service === 'null' ? null : $request->last_service,
            'dist_belt' => $request->dist_belt === 'null' ? null : $request->dist_belt,
            'last_dist_belt' => $request->last_dist_belt === 'null' ? null : $request->last_dist_belt,
            'comments' => $request->comments === 'null' ? null : $request->comments
        ]);

        return $vehicle;
    }

}
