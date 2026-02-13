<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use App\Models\CarModel;

class Offer extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**** Relationship ****/
    public function user(){
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    
    public function supplier() {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id')->withTrashed();
    }

    public function model(){
        return $this->belongsTo(CarModel::class, 'model_id', 'id');
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
    public static function createOffer($request) {

        if($request->model_id === '0') {//other model
            $model = CarModel::create([
                'name' => $request->model,
                'brand_id' => $request->brand_id
            ]);

            $model_id = $model->id;
        } else 
            $model_id = $request->model_id === 'null' ? null : $request->model_id;

        $isSupplier = Auth::check() && Auth::user()->getRoleNames()[0] === 'Supplier';
        $isUser = Auth::user()->getRoleNames()[0] === 'User';
        $supplier_id = match (true) {
            $isSupplier => Auth::user()->supplier->id,
            $isUser => Auth::user()->supplier->boss_id,
            $request->supplier_id === 'null' => null,
            default => $request->supplier_id,
        };

        $offer = self::create([
            'user_id' => Auth::user()->id,
            'supplier_id' => $supplier_id,
            'model_id' => $model_id,            'car_body_id' => ($request->car_body_id && $request->car_body_id !== 'null') ? $request->car_body_id : null,
            'fuel_id' => ($request->fuel_id && $request->fuel_id !== 'null') ? $request->fuel_id : null,
            'gearbox_id' => ($request->gearbox_id && $request->gearbox_id !== 'null') ? $request->gearbox_id : null,
            'currency_id' => ($request->currency_id && $request->currency_id !== 'null') ? $request->currency_id : null,
            'offer_id' => $request->offerId === 'null' ? null : $request->offerId,
            'reg_num' => $request->reg_num === 'null' ? null : $request->reg_num,
            'mileage' => $request->mileage === 'null' ? null : $request->mileage,
            'generation' => $request->generation === 'null' ? null : $request->generation,
            'year' => $request->year === 'null' ? null : $request->year,
            'control_inspection' => $request->control_inspection === 'null' ? null : $request->control_inspection,
            'color' => $request->color === 'null' ? null : $request->color,
            'date' => $request->date === 'null' ? null : $request->date,
            'number_keys' => $request->number_keys === 'null' ? null : $request->number_keys,
            'service_book' => $request->service_book === 'null' ? 0 : $request->service_book,
            'summer_tire' => $request->summer_tire === 'null' ? 0 : $request->summer_tire,
            'winter_tire' => $request->winter_tire === 'null' ? 0 : $request->winter_tire,
            'last_service' => $request->last_service === 'null' ? null : $request->last_service,
            'last_service_date' => $request->last_service_date === 'null' ? null : $request->last_service_date,
            'dist_belt' => $request->dist_belt === 'null' ? 0 : $request->dist_belt,
            'last_dist_belt' => $request->last_dist_belt === 'null' ? null : $request->last_dist_belt,      
            'last_dist_belt_date' => $request->last_dist_belt_date === 'null' ? null : $request->last_dist_belt_date,
            'chassis' => $request->chassis === 'null' ? null : $request->chassis,
            'engine' => $request->engine === 'null' ? null : $request->engine,
            'car_name' => $request->car_name === 'null' ? null : $request->car_name,
            'comment' => $request->comment === 'null' ? null : $request->comment,
            'price' => $request->price === 'null' ? null : $request->price,
            'terms_other_conditions' => $request->terms_other_conditions === 'null' ? null : $request->terms_other_conditions
        ]);

        return $offer;
    }

    public static function updateOffer($request, $offer) {

        if($request->model_id === '0') {//other model
            $model = CarModel::create([
                'name' => $request->model,
                'brand_id' => $request->brand_id
            ]);

            $model_id = $model->id;
        } else 
            $model_id = $request->model_id === 'null' ? null : $request->model_id;

        $offer->update([
            'model_id' => $model_id,            
            'car_body_id' => ($request->car_body_id && $request->car_body_id !== 'null') ? $request->car_body_id : null,
            'fuel_id' => ($request->fuel_id && $request->fuel_id !== 'null') ? $request->fuel_id : null,
            'gearbox_id' => ($request->gearbox_id && $request->gearbox_id !== 'null') ? $request->gearbox_id : null,
            'currency_id' => ($request->currency_id && $request->currency_id !== 'null') ? $request->currency_id : null,
            'offer_id' => $request->offerId === 'null' ? null : $request->offerId,
            'reg_num' => $request->reg_num === 'null' ? null : $request->reg_num,
            'mileage' => $request->mileage === 'null' ? null : $request->mileage,
            'generation' => $request->generation === 'null' ? null : $request->generation,
            'year' => $request->year === 'null' ? null : $request->year,
            'control_inspection' => $request->control_inspection === 'null' ? null : $request->control_inspection,
            'color' => $request->color === 'null' ? null : $request->color,
            'date' => $request->date === 'null' ? null : $request->date,
            'number_keys' => $request->number_keys === 'null' ? null : $request->number_keys,
            'service_book' => $request->service_book === 'null' ? 0 : $request->service_book,
            'summer_tire' => $request->summer_tire === 'null' ? 0 : $request->summer_tire,
            'winter_tire' => $request->winter_tire === 'null' ? 0 : $request->winter_tire,
            'last_service' => $request->last_service === 'null' ? null : $request->last_service,
            'last_service_date' => $request->last_service_date === 'null' ? null : $request->last_service_date,
            'dist_belt' => $request->dist_belt === 'null' ? 0 : $request->dist_belt,
            'last_dist_belt' => $request->last_dist_belt === 'null' ? null : $request->last_dist_belt,      
            'last_dist_belt_date' => $request->last_dist_belt_date === 'null' ? null : $request->last_dist_belt_date,
            'chassis' => $request->chassis === 'null' ? null : $request->chassis,
            'engine' => $request->engine === 'null' ? null : $request->engine,
            'car_name' => $request->car_name === 'null' ? null : $request->car_name,
            'comment' => $request->comment === 'null' ? null : $request->comment,
            'price' => $request->price === 'null' ? null : $request->price,
            'terms_other_conditions' => $request->terms_other_conditions === 'null' ? null : $request->terms_other_conditions
        ]);

        return $offer;
    }

    public static function deleteOffer($id) {
        self::deleteOffers(array($id));
    }

    public static function deleteOffers($ids) {
        foreach ($ids as $id) {
            $offer = self::find($id);
            $offer->delete();
        }
    }

}