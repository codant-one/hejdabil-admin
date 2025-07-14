<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgreementRequest;
use App\Http\Requests\VehicleRequest;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use Spatie\Permission\Middlewares\PermissionMiddleware;

use App\Models\Agreement;
use App\Models\AgreementType;
use App\Models\Vehicle;
use App\Models\Brand;
use App\Models\CarModel;
use App\Models\CarBody;
use App\Models\Gearbox;
use App\Models\Iva;
use App\Models\Fuel;
use App\Models\DocumentType;
use App\Models\State;
use App\Models\Client;
use App\Models\AgreementClient;
use App\Models\VehicleClient;
use App\Models\Guaranty;
use App\Models\GuarantyType;
use App\Models\InsuranceCompany;
use App\Models\InsuranceType;
use App\Models\Currency;
use App\Models\PaymentType;

class AgreementController extends Controller
{
    public function __construct()
    {
        $this->middleware(PermissionMiddleware::class . ':view agreements|administrator')->only(['index']);
        $this->middleware(PermissionMiddleware::class . ':create agreements|administrator')->only(['store']);
        $this->middleware(PermissionMiddleware::class . ':edit agreements|administrator')->only(['update']);
        $this->middleware(PermissionMiddleware::class . ':delete agreements|administrator')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $limit = $request->has('limit') ? $request->limit : 10;

            $query = Agreement::with([
                        'agreement_type',
                        'guaranty',
                        'guaranty_type',
                        'insurance_company',
                        'insurance_type',
                        'currency',
                        'iva',
                        'payment_type',
                        'vehicle_interchange',
                        'supplier',
                        'agreement_client',
                        'vehicle_client',
                        'supplier.user'
                    ])->applyFilters(
                        $request->only([
                            'search',
                            'orderByField',
                            'guaranty_id',
                            'guaranty_type_id',
                            'insurance_company_id',
                            'insurance_type_id',
                            'currency_id',
                            'payment_type_id',
                            'vehicle_interchange_id',
                            'vehicle_client_id',
                            'supplier_id'
                        ])
                    );

            $count = $query->count();

            $agreements = ($limit == -1) ? $query->paginate($query->count()) : $query->paginate($limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'agreements' => $agreements,
                    'agreementsTotalCount' => $count
                ]
            ]);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
              'success' => false,
              'message' => 'database_error',
              'exception' => $ex->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AgreementRequest $request)
    {
        try {

            $client = null;

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

            if ( $request->has("client_id") )
                $request->merge([
                    "client_id" => $request->save_client === 'true' ? $client->id : ($request->client_id === 'null' ? null : $request->client_id)
                ]);
            else
                $request->request->add([
                    'client_id' => $request->save_client === 'true' ? $client->id : null
                ]);


            //Create Vehicle
            $vehicle = null;
            $vehicleRequest = VehicleRequest::createFrom($request);

            $validate = Validator::make($vehicleRequest->all(), $vehicleRequest->rules(), $vehicleRequest->messages());
            if($validate->fails()){
                $vehicleRequest->failedValidation($validate);
            }

            //Set Vehicle State ID on Sold
            if ( !$vehicleRequest->has('state_id') ){
                $vehicleRequest->request->add([
                    'state_id' => 12
                ]);
            }
            elseif ( $vehicleRequest->has('state_id') && 
                     ($vehicleRequest->state_id === 'null' || empty($vehicleRequest->state_id))
                   ){
                $vehicleRequest->merge([
                    "state_id" => 12
                ]);
            }

            //If Vehicle Not Exist
            if ( !$vehicleRequest->has('vehicle_id') || 
                 ($vehicleRequest->has('vehicle_id') && $vehicleRequest->vehicle_id === 'null')
               ){
                $vehicle = Vehicle::createVehicle($vehicleRequest);
                $vehicle = Vehicle::updateVehicle($vehicleRequest, $vehicle);

                if ( $request->has("vehicle_id") )
                    $request->merge([
                        "vehicle_id" => $vehicle->id
                    ]);
                else
                    $request->request->add([
                        'vehicle_id' => $vehicle->id
                    ]);

                VehicleClient::createClient($request);
            }
            else {
                $vehicle = Vehicle::find($vehicleRequest->vehicle_id);
            }
            
            $vehicle = Vehicle::with(['vehicle_client'])->find($vehicle->id);
            
            //Get Client ID
            if ( $request->has("client_id") )
                $request->merge([
                    "client_id" => $vehicle->vehicle_client->client_id
                ]);
            else
                $request->request->add([
                    'client_id' => $vehicle->vehicle_client->client_id
                ]);

            //Set VehicleClient ID
            //Get Client ID
            if ( $request->has("client_id") )
                $request->merge([
                    "vehicle_client_id" => $vehicle->vehicle_client->id
                ]);
            else
                $request->request->add([
                    'vehicle_client_id' => $vehicle->vehicle_client->id
                ]);


            if ($request->has("intercambie") && $request->intercambie === 'true'){
                $request->merge(['reg_num' => $request->reg_num_interchange ]);
                $request->merge(['model' => $request->model_interchange ]);
                $request->merge(['brand_id' => $request->brand_id_interchange ]);
                $request->merge(['model_id' => $request->model_id_interchange ]);
                $request->merge(['car_body_id' => $request->car_body_id_interchange ]);
                $request->merge(['gearbox_id' => $request->gearbox_id_interchange ]);
                $request->merge(['iva_purchase_id' => $request->iva_purchase_id_interchange ]);
                $request->merge(['fuel_id' => $request->fuel_id_interchange ]);
                $request->merge(['state_id' => $request->state_id_interchange ]);
                $request->merge(['mileage' => $request->mileage_interchange ]);
                $request->merge(['generation' => $request->generation_interchange ]);
                $request->merge(['year' => $request->year_interchange ]);
                $request->merge(['control_inspection' => $request->control_inspection_interchange ]);
                $request->merge(['color' => $request->color_interchange ]);
                $request->merge(['purchase_price' => $request->purchase_price_interchange ]);
                $request->merge(['purchase_date' => $request->purchase_date_interchange ]);
                $request->merge(['number_keys' => $request->number_keys_interchange ]);
                $request->merge(['service_book' => $request->service_book_interchange ]);
                $request->merge(['summer_tire' => $request->summer_tire_interchange ]);
                $request->merge(['winter_tire' => $request->winter_tire_interchange ]);
                $request->merge(['last_service' => $request->last_service_interchange ]);
                $request->merge(['dist_belt' => $request->dist_belt_interchange ]);
                $request->merge(['last_dist_belt' => $request->last_dist_belt_interchange ]);
                $request->merge(['comments' => $request->comments_interchange ]);

                //Create Vehicle Interchange
                $vehicleInterchange = null;
                $vehicleRequest = VehicleRequest::createFrom($request);

                $validate = Validator::make($vehicleRequest->all(), $vehicleRequest->rules(), $vehicleRequest->messages());
                if($validate->fails()){
                    $vehicleRequest->failedValidation($validate);
                }

                //Set Vehicle State ID on InStock
                if ( !$vehicleRequest->has('state_id') ){
                    $vehicleRequest->request->add([
                        'state_id' => 10
                    ]);
                }
                elseif ( $vehicleRequest->has('state_id') && 
                        ($vehicleRequest->state_id === 'null' || empty($vehicleRequest->state_id))
                    ){
                    $vehicleRequest->merge([
                        "state_id" => 10
                    ]);
                }

                $vehicleInterchange = Vehicle::createVehicle($vehicleRequest);
                $vehicleInterchange = Vehicle::updateVehicle($vehicleRequest, $vehicleInterchange);

                if ( $request->has("vehicle_interchange_id") )
                    $request->merge([
                        "vehicle_interchange_id" => $vehicleInterchange->id
                    ]);
                else
                    $request->request->add([
                        'vehicle_interchange_id' => $vehicleInterchange->id
                    ]);
            }

            //Create Agreement
            $agreement = Agreement::createAgreement($request);
            //Get Agreement ID
            $request->request->add([
                'agreement_id' => $agreement->id
            ]);
            
            //Create Agreement Client.
            $agreementClient = AgreementClient::createClient($request);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'agreement' => Agreement::find($agreement->id),
                    'agreementClient' => AgreementClient::find($agreementClient->id),
                    'vehicleClient' => VehicleClient::find($vehicle->vehicle_client->id)
                ]
            ]);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error '.$ex->getMessage(),
                'exception' => $ex->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {

            $agreement = Agreement::with([
                        'agreement_type',
                        'guaranty',
                        'guaranty_type',
                        'insurance_company',
                        'insurance_type',
                        'currency',
                        'iva',
                        'payment_type',
                        'vehicle_interchange',
                        'supplier',
                        'agreement_client',
                        'vehicle_client',
                        'supplier.user'
                    ])->find($id);

            if (!$agreement)
                return response()->json([
                    'sucess' => false,
                    'feedback' => 'not_found',
                    'message' => 'Avtalet hittades inte'
                ], 404);

            if (Auth::user()->getRoleNames()[0] === 'Supplier') {
                $clients = Client::where('supplier_id', Auth::user()->supplier->id)->get();
            } else {
                $clients = Client::all();
            }

            return response()->json([
                'success' => true,
                'data' => [ 
                    'agreement' => $agreement,
                    'brands' => Brand::all(),
                    'models' => CarModel::with(['brand'])->get(),
                    'gearboxes' => Gearbox::all(),
                    'carbodies'  => CarBody::all(),
                    'ivas'  => Iva::all(),
                    'fuels'  => Fuel::all(),
                    'states'  => State::all(),
                    'guaranties'  => Guaranty::all(),
                    'guarantyTypes'  => GuarantyType::all(),
                    'insuranceCompanies'  => InsuranceCompany::all(),
                    'insuranceTypes'  => InsuranceType::all(),
                    'currencies'  => Currency::all(),
                    'paymenttypes'  => PaymentType::all(),
                    'clients' => $clients
                ]
            ]);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error',
                'exception' => $ex->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
     public function update(Request $request, $id): JsonResponse
    {
        try {
            $agreement = Agreement::with([
                'agreement_client',
                'vehicle_client'
            ])->find($id);
        
            if (!$agreement)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Avtalet hittades inte'
                ], 404);
            
            
            $client = null;
            if($request->save_client === 'true' && !empty($agreement->agreement_client->client_id) ) {
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

            if ( $request->has("client_id") )
                $request->merge([
                    "client_id" => $request->save_client === 'true' ? $client->id : ($request->client_id === 'null' ? $agreement->agreement_client->client_id : $request->client_id)
                ]);
            else
                $request->request->add([
                    'client_id' => $request->save_client === 'true' ? $client->id : $agreement->agreement_client->client_id
                ]);
            
            //Update Agreement
            $agreement = Agreement::updateAgreement($request, $agreement); 

            //Update Agreement Client.
            $agreementClient = AgreementClient::where('agreement_id', $agreement->id)->first();
            $agreementClient = AgreementClient::updateClient($request, $agreementClient); 

            //Update Vehicle Client.
            $vehicleClient = VehicleClient::find($agreement->vehicle_client_id);
            $vehicleClient = VehicleClient::updateClient($request, $vehicleClient); 

            return response()->json([
                'success' => true,
                'data' => [ 
                    'agreement' => Agreement::find($agreement->id),
                    'agreementClient' => $agreementClient,
                    'vehicleClient' => $vehicleClient
                ]
            ], 200);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error',
                'exception' => $ex->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {

            $agreement = Agreement::find($id);
        
            if (!$agreement)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Avtalet hittades inte'
                ], 404);
            

            $agreement->deleteAgreement($id);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'agreement' => $agreement
                ]
            ], 200);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error',
                'exception' => $ex->getMessage()
            ], 500);
        }
    }

    public function info() {

        try {

            $agreement_id = Agreement::whereNull('supplier_id')->count();
            $vehicles = 
                Vehicle::with(['model.brand'])
                       ->where([
                            ['user_id', Auth::user()->id], 
                            ['state_id', 10]
                       ])->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'brands' => Brand::all(),
                    'models' => CarModel::with(['brand'])->get(),
                    'gearboxes' => Gearbox::all(),
                    'carbodies'  => CarBody::all(),
                    'ivas'  => Iva::all(),
                    'fuels'  => Fuel::all(),
                    'states'  => State::all(),
                    'guaranties'  => Guaranty::all(),
                    'guarantyTypes'  => GuarantyType::all(),
                    'insuranceCompanies'  => InsuranceCompany::all(),
                    'insuranceTypes'  => InsuranceType::all(),
                    'currencies'  => Currency::all(),
                    'paymenttypes'  => PaymentType::all(),
                    'agreementTypes' => AgreementType::all(),
                    'agreement_id' => $agreement_id,
                    'vehicles' => $vehicles
                ]
            ]);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
              'success' => false,
              'message' => 'database_error',
              'exception' => $ex->getMessage()
            ], 500);
        }
    }
}
