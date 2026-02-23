<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionVehicle extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**** Relationship ****/
    public function commission() {
        return $this->belongsTo(Commission::class, 'commission_id');
    }

    public function model() {
        return $this->belongsTo(CarModel::class, 'model_id');
    }

    public function fuel() {
        return $this->belongsTo(Fuel::class, 'fuel_id'); 
    }

    public function gearbox() {
        return $this->belongsTo(Gearbox::class, 'gearbox_id');
    }

    public function carbody(){
        return $this->belongsTo(CarBody::class, 'car_body_id', 'id');
    }

    /**** Scopes ****/
    public function scopeWhereSearch($query, $search) {
        $query->where('fullname', 'LIKE', '%' . $search . '%')
              ->orWhere('email', 'LIKE', '%' . $search . '%');
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

        if($request->model_id === '0') {
            $model = CarModel::create([
                'name' => $request->model,
                'brand_id' => $request->brand_id
            ]);

            $model_id = $model->id;
        } else 
            $model_id = $request->model_id === 'null' ? null : $request->model_id;

        $vehicle = self::create([
            'commission_id' => $request->commission_id === 'null' ? null : $request->commission_id,
            'model_id' => $model_id,
            'car_body_id' => $request->car_body_id === 'null' ? null : $request->car_body_id,
            'currency_id' => $request->currency_id === 'null' ? null : $request->currency_id,
            'fuel_id' => $request->fuel_id === 'null' ? null : $request->fuel_id,
            'gearbox_id' => $request->gearbox_id === 'null' ? null : $request->gearbox_id,
            'reg_num' => $request->reg_num,
            'year' => $request->year === 'null' ? null : $request->year,
            'color' => $request->color === 'null' ? null : $request->color,
            'chassis' => $request->chassis === 'null' ? null : $request->chassis,
            'engine' => $request->engine === 'null' ? null : $request->engine,
            'car_name' => $request->car_name === 'null' ? null : $request->car_name,
            'mileage' => $request->mileage === 'null' ? null : $request->mileage,
            'generation' => $request->generation === 'null' ? null : $request->generation,
            'control_inspection' => $request->control_inspection === 'null' ? null : $request->control_inspection,
            'date' => $request->date === 'null' ? null : $request->date,
            'last_service' => $request->last_service === 'null' ? null : $request->last_service,
            'last_service_date' => $request->last_service_date === 'null' ? null : $request->last_service_date,
            'dist_belt' => $request->dist_belt === 'null' ? null : $request->dist_belt,
            'last_dist_belt' => $request->last_dist_belt === 'null' ? null : $request->last_dist_belt,
            'last_dist_belt_date' => $request->last_dist_belt_date === 'null' ? null : $request->last_dist_belt_date,
            'number_keys' => $request->number_keys === 'null' ? null : $request->number_keys,
            'service_book' => ( $request->service_book === 'null' || empty($request->service_book) ) ? 0 : $request->service_book,
            'summer_tire' => ( $request->summer_tire === 'null' || empty($request->summer_tire) ) ? 0 : $request->summer_tire,
            'winter_tire' => ( $request->winter_tire === 'null' || empty($request->winter_tire) ) ? 0 : $request->winter_tire,
            'comments' => $request->comments === 'null' ? null : $request->comments
        ]);

        return $vehicle;
    }

    public static function updateVehicle($request, $vehicle) {

        if($request->model_id === '0') {
            $model = CarModel::create([
                'name' => $request->model,
                'brand_id' => $request->brand_id
            ]);

            $model_id = $model->id;
        } else 
            $model_id = $request->model_id === 'null' ? null : $request->model_id;

        $vehicle->update([
            'model_id' => $model_id,
            'car_body_id' => $request->car_body_id === 'null' ? null : $request->car_body_id,
            'currency_id' => $request->currency_id === 'null' ? null : $request->currency_id,
            'fuel_id' => $request->fuel_id === 'null' ? null : $request->fuel_id,
            'gearbox_id' => $request->gearbox_id === 'null' ? null : $request->gearbox_id,
            'reg_num' => $request->reg_num,
            'year' => $request->year === 'null' ? null : $request->year,
            'color' => $request->color === 'null' ? null : $request->color,
            'chassis' => $request->chassis === 'null' ? null : $request->chassis,
            'engine' => $request->engine === 'null' ? null : $request->engine,
            'car_name' => $request->car_name === 'null' ? null : $request->car_name,
            'mileage' => $request->mileage === 'null' ? null : $request->mileage,
            'generation' => $request->generation === 'null' ? null : $request->generation,
            'control_inspection' => $request->control_inspection === 'null' ? null : $request->control_inspection,
            'date' => $request->date === 'null' ? null : $request->date,
            'last_service' => $request->last_service === 'null' ? null : $request->last_service,
            'last_service_date' => $request->last_service_date === 'null' ? null : $request->last_service_date,
            'dist_belt' => $request->dist_belt === 'null' ? null : $request->dist_belt,
            'last_dist_belt' => $request->last_dist_belt === 'null' ? null : $request->last_dist_belt,
            'last_dist_belt_date' => $request->last_dist_belt_date === 'null' ? null : $request->last_dist_belt_date,
            'number_keys' => $request->number_keys === 'null' ? null : $request->number_keys,
            'service_book' => ( $request->service_book === 'null' || empty($request->service_book) ) ? 0 : $request->service_book,
            'summer_tire' => ( $request->summer_tire === 'null' || empty($request->summer_tire) ) ? 0 : $request->summer_tire,
            'winter_tire' => ( $request->winter_tire === 'null' || empty($request->winter_tire) ) ? 0 : $request->winter_tire,
            'comments' => $request->comments === 'null' ? null : $request->comments
        ]);

        return $vehicle;
    }

    public static function deleteVehicle($id) {
        self::deleteVehicles(array($id));
    }

    public static function deleteVehicles($ids) {
        foreach ($ids as $id) {
            $vehicle = self::find($id);
            $vehicle->delete();

            if($vehicle->file)
                deleteFile($vehicle->file);
        }
    }
}