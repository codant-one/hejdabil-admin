<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

use Carbon\Carbon;
use PDF;

use App\Http\Requests\VehicleRequest;

use App\Models\Client;
use App\Models\Vehicle;
use App\Models\AgreementClient;

class Agreement extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**** Relationship ****/
    public function agreement_type(){
        return $this->belongsTo(AgreementType::class, 'agreement_type_id', 'id');
    }

    public function guaranty(){
        return $this->belongsTo(Guaranty::class, 'guaranty_id', 'id');
    }

    public function guaranty_type(){
        return $this->belongsTo(GuarantyType::class, 'guaranty_type_id', 'id');
    }

    public function insurance_company(){
        return $this->belongsTo(InsuranceCompany::class, 'insurance_company_id', 'id');
    }

    public function insurance_type(){
        return $this->belongsTo(InsuranceType::class, 'insurance_type_id', 'id');
    }

    public function currency(){
        return $this->belongsTo(Currency::class, 'currency_id', 'id');
    }

    public function iva(){
        return $this->belongsTo(Iva::class, 'iva_id', 'id');
    }

    public function payment_types(){
        return $this->belongsTo(PaymentType::class, 'payment_type_id', 'id');
    }

    public function vehicle_interchange(){
        return $this->belongsTo(Vehicle::class, 'vehicle_interchange_id', 'id');
    }

    public function supplier() {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id')->withTrashed();
    }

    public function agreement_client(){
        return $this->hasOne(AgreementClient::class, 'agreement_id', 'id');
    }

    public function vehicle_client(){
        return $this->belongsTo(VehicleClient::class, 'vehicle_client_id', 'id');
    }


    /**** Scopes ****/
    public function scopeWhereSearch($query, $search) {
        $query->where('id', $search);
    }

    public function scopeWhereOrder($query, $orderByField, $orderBy) {
        $query->orderByRaw('(IFNULL('. $orderByField .', id)) '. $orderBy);
    }

    public function scopeApplyFilters($query, array $filters) {
        $filters = collect($filters);

        if(Auth::check() && Auth::user()->getRoleNames()[0] === 'Supplier') {
            $query->where('supplier_id', Auth::user()->supplier->id);
        } elseif ($filters->get('supplier_id') !== null) {
            $query->where('supplier_id', $filters->get('supplier_id'));
        } elseif ($filters->get('supplier_id') === null) {
            $query->whereNull('supplier_id');
        }

        if ($filters->get('search')) {
            $query->whereSearch($filters->get('search'));
        }

        if ($filters->get('guaranty_id') === '0') {
            $query->where('guaranty_id',  $filters->get('guaranty_id'));
        }

        if ($filters->get('guaranty_type_id') !== null) {
            $query->where('guaranty_type_id', $filters->get('guaranty_type_id'));
        }

        if ($filters->get('insurance_company_id') !== null) {
            $query->where('insurance_company_id', $filters->get('insurance_company_id'));
        }

        if ($filters->get('insurance_type_id') !== null) {
            $query->where('insurance_type_id', $filters->get('insurance_type_id'));
        }

        if ($filters->get('currency_id') !== null) {
            $query->where('currency_id', $filters->get('currency_id'));
        }

        if ($filters->get('payment_type_id') !== null) {
            $query->where('payment_type_id', $filters->get('payment_type_id'));
        }

        if ($filters->get('vehicle_interchange_id') !== null) {
            $query->where('vehicle_interchange_id', $filters->get('vehicle_interchange_id'));
        }

        if ($filters->get('vehicle_client_id') !== null) {
            $query->where('vehicle_client_id', $filters->get('vehicle_client_id'));
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
    public static function createAgreement($request) {

        switch ($request->agreement_type_id) {
            case 1:
                $request = self::salesAgreement($request);
                break;
        }

        $isSupplier = Auth::check() && Auth::user()->getRoleNames()[0] === 'Supplier';

        $agreement = self::create([
            'agreement_id' => $request->agreement_id,
            'supplier_id' => $isSupplier ? Auth::user()->supplier->id : null,
            'agreement_type_id' => $request->agreement_type_id,
            'vehicle_client_id' => $request->vehicle_client_id === 'null' ? null : $request->vehicle_client_id,
            'vehicle_interchange_id' => $request->vehicle_interchange_id === 'null' ? null : $request->vehicle_interchange_id,
            'guaranty_id' => $request->guaranty_id === 'null' ? null : $request->guaranty_id,
            'guaranty_type_id' => $request->guaranty_type_id === 'null' ? null : $request->guaranty_type_id,            
            'insurance_company_id' => $request->insurance_company_id === 'null' ? null : $request->insurance_company_id,
            'insurance_type_id' => $request->insurance_type_id === 'null' ? null : $request->insurance_type_id,
            'currency_id' => $request->currency_id === 'null' ? null : $request->currency_id,
            'payment_type_id' => $request->payment_type_id === 'null' ? null : $request->payment_type_id,
            'iva_id' => $request->iva_id === 'null' ? null : $request->iva_id,
            'agreement_id' => $request->agreement_id,
            'sale_date' => $request->sale_date === 'null' ? null : $request->sale_date,
            'residual_debt' => $request->residual_debt === 'null' ? null : $request->residual_debt,
            'residual_price' => $request->residual_price === 'null' ? null : $request->residual_price,
            'fair_value' => $request->fair_value === 'null' ? null : $request->fair_value,
            'price' => $request->price === 'null' ? null : $request->price,
            'iva_sale_amount' => $request->iva_sale_amount === 'null' ? null : $request->iva_sale_amount,
            'iva_sale_exclusive' => $request->iva_sale_exclusive === 'null' ? null : $request->iva_sale_exclusive,
            'discount' => $request->discount === 'null' ? null : $request->discount,
            'registration_fee' => $request->registration_fee === 'null' ? null : $request->registration_fee,
            'total_sale' => $request->total_sale === 'null' ? null : $request->total_sale,
            'advance_id' => $request->advance_id === 'null' ? null : $request->advance_id,
            'middle_price' => $request->middle_price === 'null' ? null : $request->middle_price,
            'payment_type' => $request->payment_type === 'null' ? null : $request->payment_type,
            'payment_received' => $request->payment_received === 'null' ? null : $request->payment_received,
            'payment_method_forcash' => $request->payment_method_forcash === 'null' ? null : $request->payment_method_forcash,
            'installment_amount' => $request->installment_amount === 'null' ? null : $request->installment_amount,
            'installment_contract_upon_delivery' => $request->installment_contract_upon_delivery === 'null' ? null : $request->installment_contract_upon_delivery,
            'payment_description' => $request->payment_description === 'null' ? null : $request->payment_description,
            'terms_other_conditions' => $request->terms_other_conditions === 'null' ? null : $request->terms_other_conditions,
            'terms_other_information' => $request->terms_other_information === 'null' ? null : $request->terms_other_information
        ]);

        //set agreement ID
        $request->request->add([
            'agreement_id' => $agreement->id
        ]);
        
        AgreementClient::createClient($request);

        if (!file_exists(storage_path('app/public/pdfs'))) {
            mkdir(storage_path('app/public/pdfs'), 0755,true);
        } //create a folder

        $agreement = Agreement::with([
            'agreement_type',
            'guaranty',
            'guaranty_type',
            'insurance_company',
            'insurance_type',
            'currency',
            'iva',
            'payment_types',
            'vehicle_interchange',
            'agreement_client',
            'vehicle_client.vehicle.model.brand',
            'supplier.user'
        ])->find($agreement->id);

        $user = Auth::user()->load(['userDetail']);

        PDF::loadView('pdfs.agreement', compact('agreement', 'user'))->save(storage_path('app/public/pdfs').'/'.'försäljningsavtal-'.$user->id.'-'.$agreement->agreement_id.'.pdf');

        $agreement->file = 'pdfs/'.'försäljningsavtal-'.$user->id.'-'.$agreement->agreement_id.'.pdf';
        $agreement->update();
        
        return $agreement;
    }

    public static function updateAgreement($request, $agreement) {

        $agreement->update([
            'vehicle_client_id' => ($request->vehicle_client_id === 'null' || empty($request->vehicle_client_id)) ? $agreement->vehicle_client_id : $request->vehicle_client_id,
            'vehicle_interchange_id' => ($request->vehicle_interchange_id === 'null' || empty($request->vehicle_interchange_id)) ? $agreement->vehicle_interchange_id : $request->vehicle_interchange_id,
            'guaranty_id' => ($request->guaranty_id === 'null' || empty($request->guaranty_id)) ? $agreement->guaranty_id : $request->guaranty_id,
            'guaranty_type_id' => ($request->guaranty_type_id === 'null' || empty($request->guaranty_type_id)) ? $agreement->guaranty_type_id : $request->guaranty_type_id,
            'insurance_company_id' => ($request->insurance_company_id === 'null' || empty($request->insurance_company_id)) ? $agreement->insurance_company_id : $request->insurance_company_id,
            'insurance_type_id' => ($request->insurance_type_id === 'null' || empty($request->insurance_type_id)) ? $agreement->insurance_type_id : $request->insurance_type_id,
            'payment_type_id' => ($request->payment_type_id === 'null' || empty($request->payment_type_id)) ? $agreement->payment_type_id : $request->payment_type_id,
            'iva_id' => ($request->iva_id === 'null' || empty($request->iva_id)) ? $agreement->iva_id : $request->iva_id,
            'sale_date' => ($request->sale_date === 'null' || empty($request->sale_date)) ? $agreement->sale_date : $request->sale_date,
            'residual_debt' => ($request->residual_debt === 'null' || empty($request->residual_debt)) ? $agreement->residual_debt : $request->residual_debt,
            'residual_price' => ($request->residual_price === 'null' || empty($request->residual_price)) ? $agreement->residual_price : $request->residual_price,
            'fair_value' => ($request->fair_value === 'null' || empty($request->fair_value)) ? $agreement->fair_value : $request->fair_value,
            'price' => ($request->price === 'null' || empty($request->price)) ? $agreement->price : $request->price,
            'iva_sale_amount' => ($request->iva_sale_amount === 'null' || empty($request->iva_sale_amount)) ? $agreement->iva_sale_amount : $request->iva_sale_amount,
            'iva_sale_exclusive' => ($request->iva_sale_exclusive === 'null' || empty($request->iva_sale_exclusive)) ? $agreement->iva_sale_exclusive : $request->iva_sale_exclusive,
            'discount' => ($request->discount === 'null' || empty($request->discount)) ? $agreement->discount : $request->discount,
            'registration_fee' => ($request->registration_fee === 'null' || empty($request->registration_fee)) ? $agreement->registration_fee : $request->registration_fee,
            'total_sale' => ($request->total_sale === 'null' || empty($request->total_sale)) ? $agreement->total_sale : $request->total_sale,
            'advance_id' => ($request->advance_id === 'null' || empty($request->advance_id)) ? $agreement->advance_id : $request->advance_id,
            'middle_price' => ($request->middle_price === 'null' || empty($request->middle_price)) ? $agreement->middle_price : $request->middle_price,
            'payment_type' => ($request->payment_type === 'null' || empty($request->payment_type)) ? $agreement->payment_type : $request->payment_type,
            'payment_received' => ($request->payment_received === 'null' || empty($request->payment_received)) ? $agreement->payment_received : $request->payment_received,
            'payment_method_forcash' => ($request->payment_method_forcash === 'null' || empty($request->payment_method_forcash)) ? $agreement->payment_method_forcash : $request->payment_method_forcash,
            'installment_amount' => ($request->installment_amount === 'null' || empty($request->installment_amount)) ? $agreement->installment_amount : $request->installment_amount,
            'installment_contract_upon_delivery' => ($request->installment_contract_upon_delivery === 'null' || empty($request->installment_contract_upon_delivery)) ? $agreement->installment_contract_upon_delivery : $request->installment_contract_upon_delivery,
            'payment_description' => ($request->payment_description === 'null' || empty($request->payment_description)) ? $agreement->payment_description : $request->payment_description,
            'terms_other_conditions' => ($request->terms_other_conditions === 'null' || empty($request->terms_other_conditions)) ? $agreement->terms_other_conditions : $request->terms_other_conditions,
            'terms_other_information' => ($request->terms_other_information === 'null' || empty($request->terms_other_information)) ? $agreement->terms_other_information : $request->terms_other_information
        ]);

        return $agreement;
    }

    public static function deleteAgreement($id) {
        self::deleteVehicles(array($id));
    }

    public static function deleteAgreements($ids) {
        foreach ($ids as $id) {
            $vehicle = self::find($id);
            $vehicle->delete();
        }
    }

    // agremment types
    public static function salesAgreement($request) {

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

        if ($request->vehicle_id === 'null') {//no existe

            $vehicleRequest = VehicleRequest::createFrom($request);

            $validate = Validator::make($vehicleRequest->all(), $vehicleRequest->rules(), $vehicleRequest->messages());

            if($validate->fails()) {
                $vehicleRequest->failedValidation($validate);
            }

            //Set Vehicle State ID on Sold
            $vehicleRequest->request->add(['state_id' => 12]);

            $vehicle = Vehicle::createVehicle($vehicleRequest);
            $vehicle = Vehicle::updateVehicle($vehicleRequest, $vehicle);

            if ($request->has("vehicle_id"))
                $request->merge([
                    "vehicle_id" => $vehicle->id
                ]);
            else
                $request->request->add([
                    'vehicle_id' => $vehicle->id
                ]);

            VehicleClient::createClient($request);
        } else {// existe pero no esta vendido 
            $vehicle = Vehicle::find($request->vehicle_id);
            Vehicle::sendVehicle($request, $vehicle); 
        }
        
        $vehicle = Vehicle::with(['vehicle_client'])->find($vehicle->id);

        //Set VehicleClient ID
       if ($request->has("client_id"))
            $request->merge([
                "vehicle_client_id" => $vehicle->vehicle_client->id
            ]);
        else
            $request->request->add([
                'vehicle_client_id' => $vehicle->vehicle_client->id
            ]);

        if ($request->has("intercambie") && $request->intercambie === 'true') {
            $request->merge(['reg_num' => $request->reg_num_interchange ]);
            $request->merge(['brand_id' => $request->brand_id_interchange ]);
            $request->merge(['model_id' => $request->model_id_interchange ]);
            $request->merge(['car_body_id' => $request->car_body_id_interchange ]);
            $request->merge(['iva_purchase_id' => $request->iva_purchase_id_interchange ]);
            $request->merge(['year' => $request->year_interchange ]);
            $request->merge(['color' => $request->color_interchange ]);
            $request->merge(['purchase_price' => $request->purchase_price_interchange ]);
            $request->merge(['purchase_date' => $request->purchase_date_interchange ]);
            $request->merge(['meter_reading' => $request->meter_reading_interchange ]);
            $request->merge(['chassis' => $request->chassis_interchange ]);
            $request->merge(['sale_date' => $request->sale_date_interchange ]);

            //Create Vehicle Interchange
            $vehicleRequest = VehicleRequest::createFrom($request);

            $validate = Validator::make($vehicleRequest->all(), $vehicleRequest->rules(), $vehicleRequest->messages());
            if($validate->fails()){
                $vehicleRequest->failedValidation($validate);
            }

            //Set Vehicle State ID on InStock
            $vehicleRequest->request->add(['state_id' => 10]);

            $vehicleInterchange = Vehicle::createVehicle($vehicleRequest);
            $vehicleInterchange = Vehicle::updateVehicle($vehicleRequest, $vehicleInterchange);

            if ($request->has("vehicle_interchange_id"))
                $request->merge([
                    "vehicle_interchange_id" => $vehicleInterchange->id
                ]);
            else
                $request->request->add([
                    'vehicle_interchange_id' => $vehicleInterchange->id
                ]);
        }

        return $request;

    }
    
}
