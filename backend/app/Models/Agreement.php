<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;
use PDF;

use App\Http\Requests\VehicleRequest;

use App\Models\Client;
use App\Models\Vehicle;
use App\Models\VehicleClient;
use App\Models\VehiclePayment;
use App\Models\AgreementClient;
use App\Models\Offer;
use App\Models\Commission;
use App\Models\User;
use App\Models\UserDetails;
use App\Models\Config;

class Agreement extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    /**** Relationship ****/
    public function agreement_type(){
        return $this->belongsTo(AgreementType::class, 'agreement_type_id', 'id');
    }

    public function guaranty_type(){
        return $this->belongsTo(GuarantyType::class, 'guaranty_type_id', 'id');
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

    public function user(){
        return $this->belongsTo(User::class, 'user_id', 'id');
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

    public function offer(){
        return $this->belongsTo(Offer::class, 'offer_id', 'id');
    }

    public function commission(){
        return $this->belongsTo(Commission::class, 'commission_id', 'id');
    }

    public function tokens() {
        return $this->hasMany(Token::class, 'agreement_id');
    }

    /**** Scopes ****/
    public function scopeWhereSearch($query, $search) {
        $query->where(function ($q) use ($search) {
            $q->whereHas('offer', function ($uq) use ($search) {
                $uq->where(function ($inner) use ($search) {
                    $inner->where('reg_num', 'LIKE', '%' . $search . '%');
                });
            })
            ->orWhereHas('commission.vehicle', function ($uq) use ($search) {
                $uq->where(function ($inner) use ($search) {
                    $inner->where('reg_num', 'LIKE', '%' . $search . '%');
                });
            })
            ->orWhereHas('vehicle_client.vehicle', function ($uq) use ($search) {
                $uq->where(function ($inner) use ($search) {
                    $inner->where('reg_num', 'LIKE', '%' . $search . '%');
                });
            })
            ->orWhereHas('vehicle_interchange', function ($uq) use ($search) {
                $uq->where(function ($inner) use ($search) {
                    $inner->where('reg_num', 'LIKE', '%' . $search . '%');
                });
            })
            ->orWhereHas('agreement_type', function ($uq) use ($search) {
                $uq->where(function ($inner) use ($search) {
                    $inner->where('name', 'LIKE', '%' . $search . '%');
                });
            })
            ->orWhereHas('user', function ($uq) use ($search) {
                $uq->where(function ($inner) use ($search) {
                    $inner->where('name', 'LIKE', '%' . $search . '%')
                         ->orWhere('last_name', 'LIKE', '%' . $search . '%')
                         ->orWhere('email', 'LIKE', '%' . $search . '%');
                });
            })
            ->orWhere('installment_amount', $search); 
        });
    }

    public function scopeWhereOrder($query, $orderByField, $orderBy) {
        $query->orderByRaw('(IFNULL('. $orderByField .', id)) '. $orderBy);
    }

    public function token(){
        return $this->hasOne(Token::class, 'agreement_id', 'id');
    }

    public function scopeApplyFilters($query, array $filters) {
        $filters = collect($filters);

        if ($filters->get('supplier_id') !== null) {
            $query->where('supplier_id', $filters->get('supplier_id'));
        } else if(Auth::check() && Auth::user()->getRoleNames()[0] === 'Supplier') {
            $query->where('supplier_id', Auth::user()->supplier->id);
        } else if(Auth::check() && Auth::user()->getRoleNames()[0] === 'User') {
            $query->where('supplier_id', Auth::user()->supplier->boss_id);
        }

        if ($filters->get('agreement_type_id') !== null) {
            $query->where('agreement_type_id', $filters->get('agreement_type_id'));
        }

        if ($filters->get('search')) {
            $query->whereSearch($filters->get('search'));
        }

        if ($filters->get('status') !== null) {
            $query->whereHas('tokens', function($q) use ($filters) {
                $q->where('signature_status', $filters->get('status'));
            });
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
            case 2:
                $request = self::purchaseAgreement($request);
                break;
            case 3:
                $request = self::createCommission($request);
                break;
            case 4:
                $request = self::createOffer($request);
                break;
        }

        $isSupplier = Auth::check() && Auth::user()->getRoleNames()[0] === 'Supplier';
        $isUser = Auth::user()->getRoleNames()[0] === 'User';
        $supplier_id = match (true) {
            $isSupplier => Auth::user()->supplier->id,
            $isUser => Auth::user()->supplier->boss_id,
            $request->supplier_id === 'null' => null,
            default => $request->supplier_id,
        };

        $agreement = self::create([
            'user_id' => Auth::user()->id,
            'supplier_id' => $supplier_id,
            'agreement_id' => $request->agreement_id,
            'agreement_type_id' => $request->agreement_type_id,
            'vehicle_client_id' => $request->vehicle_client_id === 'null' ? null : $request->vehicle_client_id,
            'vehicle_interchange_id' => $request->vehicle_interchange_id === 'null' ? null : $request->vehicle_interchange_id,
            'guaranty_type_id' => $request->guaranty_type_id === 'null' ? null : $request->guaranty_type_id,
            'insurance_type_id' => $request->insurance_type_id === 'null' ? null : $request->insurance_type_id,
            'currency_id' => $request->currency_id === 'null' ? null : $request->currency_id,
            'payment_type_id' => $request->payment_type_id === 'null' ? null : $request->payment_type_id,
            'iva_id' => $request->iva_id === 'null' ? null : $request->iva_id,
            'offer_id' => $request->offer_id === 'null' ? null : $request->offer_id,
            'commission_id' => $request->commission_id === 'null' ? null : $request->commission_id,
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
            'vehicle_payment_id' => $request->vehicle_payment_id === 'null' ? null : $request->vehicle_payment_id,
            'middle_price' => $request->middle_price === 'null' ? null : $request->middle_price,
            'payment_type' => $request->payment_type === 'null' ? null : $request->payment_type,
            'payment_received' => $request->payment_received === 'null' ? null : $request->payment_received,
            'payment_method_forcash' => $request->payment_method_forcash === 'null' ? null : $request->payment_method_forcash,
            'installment_amount' => $request->installment_amount === 'null' ? null : $request->installment_amount,
            'installment_contract_upon_delivery' => $request->installment_contract_upon_delivery === 'null' ? null : $request->installment_contract_upon_delivery,
            'guaranty' => $request->guaranty === 'null' ? null : $request->guaranty,
            'guaranty_description' => $request->guaranty_description === 'null' ? null : $request->guaranty_description,
            'insurance_company' => $request->insurance_company === 'null' ? null : $request->insurance_company,
            'insurance_company_description' => $request->insurance_company_description === 'null' ? null : $request->insurance_company_description,
            'payment_description' => $request->payment_description === 'null' ? null : $request->payment_description,
            'terms_other_conditions' => $request->terms_other_conditions === 'null' ? null : $request->terms_other_conditions,
            'terms_other_information' => $request->terms_other_information === 'null' ? null : $request->terms_other_information
        ]);

        //set agreement ID
        $request->request->add([
            'agreement_id' => $agreement->id
        ]);
        
        AgreementClient::createClient($request);
        
        self::generatePdf($agreement, $request);

        return $agreement;
    }

    public static function updateAgreement($request, $agreement) {

        switch ($request->agreement_type_id) {
            case 1:
                self::updateSales($request, $agreement);
                break;
            case 2:
                self::updatePurchase($request, $agreement);
                break;
            case 3:
                self::updateCommission($request, $agreement);
                break;
            case 4:
                self::updateOffer($request, $agreement);
                break;
        }

        $agreement->update([
            'vehicle_client_id' => $request->vehicle_client_id === 'null' ? null : $request->vehicle_client_id,
            'vehicle_interchange_id' => $request->vehicle_interchange_id === 'null' ? null : $request->vehicle_interchange_id,
            'guaranty_type_id' => $request->guaranty_type_id === 'null' ? null : $request->guaranty_type_id,
            'insurance_type_id' => $request->insurance_type_id === 'null' ? null : $request->insurance_type_id,
            'currency_id' => $request->currency_id === 'null' ? null : $request->currency_id,
            'payment_type_id' => $request->payment_type_id === 'null' ? null : $request->payment_type_id,
            'iva_id' => $request->iva_id === 'null' ? null : $request->iva_id,
            'offer_id' => $request->offer_id === 'null' ? null : $request->offer_id,
            'commission_id' => $request->commission_id === 'null' ? null : $request->commission_id,
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
            'vehicle_payment_id' => $request->vehicle_payment_id === 'null' ? null : $request->vehicle_payment_id,
            'middle_price' => $request->middle_price === 'null' ? null : $request->middle_price,
            'payment_type' => $request->payment_type === 'null' ? null : $request->payment_type,
            'payment_received' => $request->payment_received === 'null' ? null : $request->payment_received,
            'payment_method_forcash' => $request->payment_method_forcash === 'null' ? null : $request->payment_method_forcash,
            'installment_amount' => $request->installment_amount === 'null' ? null : $request->installment_amount,
            'installment_contract_upon_delivery' => $request->installment_contract_upon_delivery === 'null' ? null : $request->installment_contract_upon_delivery,
            'guaranty' => $request->guaranty === 'null' ? null : $request->guaranty,
            'guaranty_description' => $request->guaranty_description === 'null' ? null : $request->guaranty_description,
            'insurance_company' => $request->insurance_company === 'null' ? null : $request->insurance_company,
            'insurance_company_description' => $request->insurance_company_description === 'null' ? null : $request->insurance_company_description,
            'payment_description' => $request->payment_description === 'null' ? null : $request->payment_description,
            'terms_other_conditions' => $request->terms_other_conditions === 'null' ? null : $request->terms_other_conditions,
            'terms_other_information' => $request->terms_other_information === 'null' ? null : $request->terms_other_information
        ]);

        self::generatePdf($agreement, $request);

        return $agreement;
    }

    public static function deleteAgreement($id) {
        self::deleteAgreements(array($id));
    }

    public static function deleteAgreements($ids) {
        foreach ($ids as $id) {
            $agreement = self::find($id);
            $agreement->delete();

            if($agreement->file)
                deleteFile($agreement->file);
        }
    }

    public static function generatePdf($agreement, $request) {

        $agreement = Agreement::with([
            'agreement_type',
            'guaranty_type',
            'insurance_type',
            'currency',
            'iva',
            'offer',
            'commission.vehicle',
            'payment_types',
            'vehicle_interchange.model.brand',
            'vehicle_interchange.carbody',
            'vehicle_interchange.iva_purchase',
            'agreement_client',
            'vehicle_client.vehicle.model.brand',
            'vehicle_client.vehicle.fuel',
            'vehicle_client.vehicle.gearbox',
            'vehicle_client.vehicle.payment.payment_types',
            'supplier.user'
        ])->find($agreement->id);

        if (!file_exists(storage_path('app/public/pdfs'))) {
            mkdir(storage_path('app/public/pdfs'), 0755,true);
        } //create a folder

        if (Auth::user()->getRoleNames()[0] === 'Supplier') {
            $user = UserDetails::with(['user'])->find(Auth::user()->id);
            $company = $user->user->userDetail;
            $company->email = $user->user->email;
            $company->name = $user->user->name;
            $company->last_name = $user->user->last_name;
        } else if (Auth::user()->getRoleNames()[0] === 'User') {
            $user = User::with(['userDetail', 'supplier.boss.user.userDetail'])->find(Auth::user()->id);
            $company = $user->supplier->boss->user->userDetail;
            $company->email = $user->supplier->boss->user->email;
            $company->name = $user->supplier->boss->user->name;
            $company->last_name = $user->supplier->boss->user->last_name;
        } else { //Admin
            $configCompany = Config::getByKey('company') ?? ['value' => '[]'];
            $configLogo    = Config::getByKey('logo')    ?? ['value' => '[]'];
            $configSignature   = Config::getByKey('signature')    ?? ['value' => '[]'];
            // Extraer el "value" soportando array u object
            $getValue = function ($cfg) {
                if (is_array($cfg)) 
                    return $cfg['value'] ?? '[]';
                if (is_object($cfg) && isset($cfg->value))
                    return $cfg->value;
                return '[]';
            };
            
            $companyRaw = $getValue($configCompany);
            $logoRaw    = $getValue($configLogo);
            $signatureRaw    = $getValue($configSignature);

            $decodeSafe = function ($raw) {
                $decoded = json_decode($raw);

                if (is_string($decoded))
                    $decoded = json_decode($decoded);
            
                if (!is_object($decoded)) 
                    $decoded = (object) [];
            
                return $decoded;
            };
            
            $company = $decodeSafe($companyRaw);
            $logoObj    = $decodeSafe($logoRaw);
            $signatureObj    = $decodeSafe($signatureRaw);

            $company->logo = $logoObj->logo ?? null;
            $company->img_signature = $signatureObj->img_signature ?? null;
        }

        switch ($request->agreement_type_id) {
            case 1:
                PDF::loadView('pdfs.sales', compact('company', 'agreement'))->save(storage_path('app/public/pdfs').'/'.'försäljningsavtal-'.$agreement->vehicle_client->vehicle->reg_num.'-'.$agreement->agreement_id.'.pdf');
                $agreement->file = 'pdfs/'.'försäljningsavtal-'.$agreement->vehicle_client->vehicle->reg_num.'-'.$agreement->agreement_id.'.pdf';
                break;
            case 2:
                PDF::loadView('pdfs.purchase', compact('company', 'agreement'))->save(storage_path('app/public/pdfs').'/'.'inköpsavtal-'.$agreement->vehicle_client->vehicle->reg_num.'-'.$agreement->agreement_id.'.pdf');
                $agreement->file = 'pdfs/'.'inköpsavtal-'.$agreement->vehicle_client->vehicle->reg_num.'-'.$agreement->agreement_id.'.pdf';
                break;
            case 3:
                PDF::loadView('pdfs.mediation', compact('company', 'agreement'))->save(storage_path('app/public/pdfs').'/'.'förmedlingsavtal-'.$agreement->commission->vehicle->reg_num.'-'.$agreement->agreement_id.'.pdf');
                $agreement->file = 'pdfs/'.'förmedlingsavtal-'.$agreement->commission->vehicle->reg_num.'-'.$agreement->agreement_id.'.pdf';
                break;
            case 4:
                PDF::loadView('pdfs.business', compact('company', 'agreement'))->save(storage_path('app/public/pdfs').'/'.'prisförslag-'.$agreement->offer->reg_num.'-'.$agreement->offer->offer_id.'.pdf');
                $agreement->file = 'pdfs/'.'prisförslag-'.$agreement->offer->reg_num.'-'.$agreement->offer->offer_id.'.pdf';
                break;
        }

        $agreement->update();
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
        
        $vehicle = Vehicle::with(['client_sale'])->find($vehicle->id);

        //Set VehicleClient ID
       if ($request->has("client_id"))
            $request->merge([
                "vehicle_client_id" => $vehicle->client_sale->id
            ]);
        else
            $request->request->add([
                'vehicle_client_id' => $vehicle->client_sale->id
            ]);

        if ($request->has("interchange") && $request->interchange === 'true') {
            $request->merge(['reg_num' => $request->reg_num_interchange ]);
            $request->merge(['brand_id' => $request->brand_id_interchange ]);
            $request->merge(['model_id' => $request->model_id_interchange ]);
            $request->merge(['model' => $request->model_interchange ]);
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
            $vehicleRequest->request->add(['type' => '2']);

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

    public static function updateSales($request, $agreement) {
        //Update Vehicle Client.
        $vehicleClient = VehicleClient::find($request->vehicle_client_id);
        $vehicleClient->updateClient($request, $vehicleClient);
        
        //Update Vehicle.
        $vehicle = Vehicle::find($vehicleClient->vehicle_id);
        $request->request->add(['state_id' => $vehicle->state_id]);
        $vehicle->updateVehicle($request, $vehicle);

        //Update Vehicle interchange.
        if ($request->has("interchange") && $request->interchange === 'true') {
            $request->merge(['reg_num' => $request->reg_num_interchange ]);
            $request->merge(['brand_id' => $request->brand_id_interchange ]);
            $request->merge(['model_id' => $request->model_id_interchange ]);
            $request->merge(['model' => $request->model_interchange ]);
            $request->merge(['car_body_id' => $request->car_body_id_interchange ]);
            $request->merge(['iva_purchase_id' => $request->iva_purchase_id_interchange ]);
            $request->merge(['year' => $request->year_interchange ]);
            $request->merge(['color' => $request->color_interchange ]);
            $request->merge(['purchase_price' => $request->purchase_price_interchange ]);
            $request->merge(['purchase_date' => $request->purchase_date_interchange ]);
            $request->merge(['meter_reading' => $request->meter_reading_interchange ]);
            $request->merge(['chassis' => $request->chassis_interchange ]);
            $request->merge(['sale_date' => $request->sale_date_interchange ]);

            $vehicleInterchange = Vehicle::find($request->vehicle_interchange_id);
            $request->request->add(['state_id' => $vehicleInterchange->state_id]);
            $vehicleInterchange->updateVehicle($request, $vehicleInterchange);
        }

        //Update Agreement Client.
        $agreementClient = AgreementClient::where('agreement_id', $agreement->id)->first();
        $agreementClient->updateClient($request, $agreementClient);
    }

    public static function purchaseAgreement($request) {

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


        $vehicleRequest = VehicleRequest::createFrom($request);

        $validate = Validator::make($vehicleRequest->all(), $vehicleRequest->rules(), $vehicleRequest->messages());

        if($validate->fails()) {
            $vehicleRequest->failedValidation($validate);
        }

        //Set Vehicle State ID in stock
        $vehicleRequest->request->add(['state_id' => 10]);

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
        
        $vehicle = Vehicle::with(['client_purchase'])->find($vehicle->id);

        //Set VehicleClient ID
       if ($request->has("client_id"))
            $request->merge([
                "vehicle_client_id" => $vehicle->client_purchase->id
            ]);
        else
            $request->request->add([
                'vehicle_client_id' => $vehicle->client_purchase->id
            ]);

        VehiclePayment::createVehiclePayment($request);

        return $request;

    }

    public static function updatePurchase($request, $agreement) {
        //Update Vehicle Client.
        $vehicleClient = VehicleClient::find($request->vehicle_client_id);
        $vehicleClient->updateClient($request, $vehicleClient);
       
        //Update Vehicle.
        $vehicle = Vehicle::find($vehicleClient->vehicle_id);
        $request->request->add(['state_id' => 10]);
        $vehicle->updateVehicle($request, $vehicle);

        //Update Vehicle Payment.
        $vehicle_payment = VehiclePayment::where('vehicle_id', $vehicle->id)->first();
        $vehicle_payment->updateVehiclePayment($request, $vehicle_payment);

        //Update Agreement Client.
        $agreementClient = AgreementClient::where('agreement_id', $agreement->id)->first();
        $agreementClient->updateClient($request, $agreementClient);
        
        return $request;

    }
    
    public static function createOffer($request) {

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

        $offer = Offer::createOffer($request);

        $request->request->add([
            'offer_id' => $offer->id
        ]);

        return $request;
    }

    public static function updateOffer($request, $agreement) {
        $offer = Offer::find($agreement->offer_id);
        $offer->updateOffer($request, $offer);
    }

    public static function createCommission($request) {

        $commission = Commission::createCommission($request);

        $request->request->add([
            'commission_id' => $commission->id
        ]);

        return $request;
    }

    public static function updateCommission($request, $agreement) {
        $commission = Commission::find($agreement->commission_id);
        $commission->updateCommission($request, $commission);
    }

    public static function coordinates($agreement, $coordinateType) {
        switch ($agreement->agreement_type_id) {
            case 1: // Sales
            case 2: // Purchase
                return $coordinateType === 'x' ? 60.3543 : 87.8653;
                break;
            case 3: // Commission
            case 4: // Offer    
                return $coordinateType === 'x' ? 12.9134 : 86.1954;
                break;
            default:
                return null;
        }

    }
    
}
