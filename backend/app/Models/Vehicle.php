<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

use App\Models\CarModel;
use App\Models\Client;
use App\Models\VehicleClient;

use Carbon\Carbon;
use PDF;

class Vehicle extends Model
{
    use HasFactory;

    protected $guarded = [];
    
    /**** Relationship ****/
    public function carbody(){
        return $this->belongsTo(CarBody::class, 'car_body_id', 'id');
    }  

    public function model(){
        return $this->belongsTo(CarModel::class, 'model_id', 'id');
    }

    public function gearbox(){
        return $this->belongsTo(Gearbox::class, 'gearbox_id', 'id');
    }

    public function iva_purchase(){
        return $this->belongsTo(Iva::class, 'iva_purchase_id', 'id');
    }

    public function iva_sale(){
        return $this->belongsTo(Iva::class, 'iva_sale_id', 'id');
    }

    public function currency_purchase(){
        return $this->belongsTo(Currency::class, 'currency_purchase_id', 'id');
    }

    public function currency_sale(){
        return $this->belongsTo(Currency::class, 'currency_sale_id', 'id');
    }

    public function fuel(){
        return $this->belongsTo(Fuel::class, 'fuel_id', 'id');
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

    public function costs(){
        return $this->hasMany(VehicleCost::class, 'vehicle_id', 'id');
    }

    public function documents(){
        return $this->hasMany(VehicleDocument::class, 'vehicle_id', 'id');
    }

    public function vehicle_client(){
        return $this->hasOne(VehicleClient::class, 'vehicle_id', 'id');
    }

    public function vehicle_interchange(){
        return $this->hasOne(Agreement::class, 'vehicle_interchange_id', 'id');
    }

    /**** Scopes ****/
    public function scopeWhereSearch($query, $search) {
        $query->where('reg_num', 'LIKE', '%' . $search . '%');
    }

    public function scopeWhereOrder($query, $orderByField, $orderBy) {
        $query->orderByRaw('(IFNULL('. $orderByField .', id)) '. $orderBy);
    }

    public function scopeApplyFilters($query, array $filters) {
        $filters = collect($filters);

        if ($filters->get('search')) {
            $query->whereSearch($filters->get('search'));
        }

        if ($filters->get('isSold') === '0') {
            $query->where('state_id', '<>', 12);
        }

        if ($filters->get('state_id') !== null) {
            $query->where('state_id', $filters->get('state_id'));
        }

        if ($filters->get('brand_id') !== null) {
            $query->whereHas('model.brand', function ($query) use ($filters) {
                $query->where('id', $filters->get('brand_id'));
            });
        }

        if ($filters->get('model_id') !== null) {
            $query->where('model_id', $filters->get('model_id'));
        }

        if ($filters->get('year') !== null) {
            $query->where('year', $filters->get('year'));
        }

        if ($filters->get('gearbox_id') !== null) {
            $query->where('gearbox_id', $filters->get('gearbox_id'));
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
            'user_id' => Auth::user()->id,
            'reg_num' => $request->reg_num
        ]);
        
        $vehicle = self::with(['user', 'model.brand', 'state', 'iva_purchase', 'costs'])->find($vehicle->id);
        $name = $vehicle->reg_num;

        if (!file_exists(storage_path('app/public/pdfs'))) {
            mkdir(storage_path('app/public/pdfs'), 0755,true);
        } //create a folder

        PDF::loadView('pdfs.vehicle', compact('vehicle'))->save(storage_path('app/public/pdfs').'/'.Str::slug($name).'-sammanställning-'.$vehicle->id.'.pdf');

        $vehicle->file = 'pdfs/'.Str::slug($name).'-sammanställning-'.$vehicle->id.'.pdf';
        $vehicle->update();

        return $vehicle;
    }

    public static function updateVehicle($request, $vehicle) {

        if($request->model_id === '0') {//other model
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
            'gearbox_id' => $request->gearbox_id === 'null' ? null : $request->gearbox_id,
            'iva_purchase_id' => $request->iva_purchase_id === 'null' ? null : $request->iva_purchase_id,
            'currency_purchase_id' => $request->currency_id === 'null' ? null : $request->currency_id,
            'currency_sale_id' => $request->currency_id === 'null' ? null : $request->currency_id,
            'fuel_id' => $request->fuel_id === 'null' ? null : $request->fuel_id,
            'state_id' => $request->state_id === 'null' ? 10 : $request->state_id,
            'mileage' => $request->mileage === 'null' ? null : $request->mileage,
            'generation' => $request->generation === 'null' ? null : $request->generation,
            'year' => $request->year === 'null' ? null : $request->year,
            'control_inspection' => $request->control_inspection === 'null' ? null : $request->control_inspection,
            'color' => $request->color === 'null' ? null : $request->color,
            'purchase_price' => $request->purchase_price === 'null' ? null : $request->purchase_price,
            'purchase_date' => $request->purchase_date === 'null' ? null : $request->purchase_date,
            'number_keys' => $request->number_keys === 'null' ? null : $request->number_keys,
            'service_book' => ( $request->service_book === 'null' || empty($request->service_book) ) ? 0 : $request->service_book,
            'summer_tire' => ( $request->summer_tire === 'null' || empty($request->summer_tire) ) ? 0 : $request->summer_tire,
            'winter_tire' => ( $request->winter_tire === 'null' || empty($request->winter_tire) ) ? 0 : $request->winter_tire,
            'last_service' => $request->last_service === 'null' ? null : $request->last_service,
            'dist_belt' => ( $request->dist_belt === 'null' || empty($request->dist_belt) ) ? 0 : $request->dist_belt,
            'last_dist_belt' => $request->last_dist_belt === 'null' ? null : $request->last_dist_belt,
            'comments' => $request->comments === 'null' ? null : $request->comments,
            'meter_reading' => $request->meter_reading === 'null' ? null : $request->meter_reading,
            'chassis' => $request->chassis === 'null' ? null : $request->chassis,
            'sale_price' => $request->sale_price === 'null' ? null : $request->sale_price,
            'sale_date' => $request->sale_date === 'null' ? null : $request->sale_date,
            'iva_sale_id' => $request->iva_sale_id === 'null' ? null : $request->iva_sale_id,
            'sale_comments' => $request->sale_comments === 'null' ? null : $request->sale_comments,
            'iva_sale_amount' => $request->iva_sale_amount === 'null' ? null : $request->iva_sale_amount,
            'iva_sale_exclusive' => $request->iva_sale_exclusive === 'null' ? null : $request->iva_sale_exclusive,
            'total_sale' => $request->total_sale === 'null' ? null : $request->total_sale,
            'discount' => $request->discount === 'null' ? null : $request->discount,
            'registration_fee' => $request->registration_fee === 'null' ? null : $request->registration_fee
        ]);

        $vehicle = self::with(['user', 'model.brand', 'state', 'iva_purchase', 'costs'])->find($vehicle->id);
        $name = $vehicle->reg_num;

        if (!file_exists(storage_path('app/public/pdfs'))) {
            mkdir(storage_path('app/public/pdfs'), 0755,true);
        } //create a folder

        PDF::loadView('pdfs.vehicle', compact('vehicle'))->save(storage_path('app/public/pdfs').'/'.Str::slug($name).'-sammanställning-'.$vehicle->id.'.pdf');

        $vehicle->file = 'pdfs/'.Str::slug($name).'-sammanställning-'.$vehicle->id.'.pdf';
        $vehicle->update();

        return $vehicle;
    }

    public static function sendVehicle($request, $vehicle) {

        $vehicle->update([
            'state_id' => 12,           
            'sale_price' => $request->sale_price === 'null' ? null : $request->sale_price,
            'sale_date' => $request->sale_date === 'null' ? null : $request->sale_date,
            'iva_sale_id' => $request->iva_sale_id === 'null' ? null : $request->iva_sale_id,
            'sale_comments' => $request->sale_comments === 'null' ? null : $request->sale_comments,
            'iva_sale_amount' => $request->iva_sale_amount === 'null' ? null : $request->iva_sale_amount,
            'iva_sale_exclusive' => $request->iva_sale_exclusive === 'null' ? null : $request->iva_sale_exclusive,
            'total_sale' => $request->total_sale === 'null' ? null : $request->total_sale,
            'discount' => $request->discount === 'null' ? null : $request->discount,
            'registration_fee' => $request->registration_fee === 'null' ? null : $request->registration_fee
        ]);

        $vehicle = self::with(['user', 'model.brand', 'state', 'iva_purchase', 'costs'])->find($vehicle->id);
        $name = $vehicle->reg_num;

        if (!file_exists(storage_path('app/public/pdfs'))) {
            mkdir(storage_path('app/public/pdfs'), 0755,true);
        } //create a folder

        PDF::loadView('pdfs.vehicle', compact('vehicle'))->save(storage_path('app/public/pdfs').'/'.Str::slug($name).'-sammanställning-'.$vehicle->id.'.pdf');

        $vehicle->file = 'pdfs/'.Str::slug($name).'-sammanställning-'.$vehicle->id.'.pdf';
        $vehicle->update();

        if($request->save_client === 'true') {
            $request->supplier_id = 'null';
            $client = Client::createClient($request);
            $order_id = Client::where('supplier_id', $client->supplier_id)
                            ->withTrashed()
                            ->latest('order_id')
                            ->first()
                            ->order_id ?? 0;

            $client->order_id = $order_id + 1;
            $client->update();
        }

        if ($request->has("client_id"))
            $request->merge([
                "client_id" => $request->save_client === 'true' ? $client->id : ($request->client_id === 'null' ? null : $request->client_id)
            ]);
        else
            $request->request->add([
                'client_id' => $request->save_client === 'true' ? $client->id : ($request->client_id === 'null' ? null : $request->client_id)
            ]);
        
        if ($request->has("vehicle_id"))
            $request->merge([
                "vehicle_id" => $vehicle->id
            ]);
        else
            $request->request->add([
                'vehicle_id' => $vehicle->id
            ]);

        VehicleClient::createClient($request);

        return $vehicle;
    }

    public static function deleteVehicle($id) {
        self::deleteVehicles(array($id));
    }

    public static function deleteVehicles($ids) {
        foreach ($ids as $id) {
            $vehicle = self::find($id);
            $vehicle->delete();
        }
    }

}
