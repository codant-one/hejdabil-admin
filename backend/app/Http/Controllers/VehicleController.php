<?php

namespace App\Http\Controllers;

use App\Http\Requests\VehicleRequest;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

use Spatie\Permission\Middlewares\PermissionMiddleware;

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
use App\Models\ClientType;
use App\Models\Identification;
use App\Models\Currency;
use App\Models\Supplier;
use App\Models\VehicleDocument;
use App\Models\SupplierActivity;

use App\Services\CacheService;

class VehicleController extends Controller
{
    public function __construct()
    {
        $this->middleware(PermissionMiddleware::class . ':view stock|administrator')->only(['index']);
        $this->middleware(PermissionMiddleware::class . ':create stock|administrator')->only(['store']);
        $this->middleware(PermissionMiddleware::class . ':edit stock|administrator')->only(['update']);
        $this->middleware(PermissionMiddleware::class . ':delete stock|administrator')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $limit = (int) $request->input('limit', 10);

            // Keep -1 as export-all sentinel, but prevent invalid per-page values.
            if ($limit !== -1)
                $limit = max(1, $limit);
        
            $query = Vehicle::with([
                        'supplier' => function ($q) {
                            $q->select('id', 'user_id', 'boss_id', 'deleted_at')
                              ->withTrashed()
                              ->with(['user' => fn($u) => $u->select('id', 'name', 'last_name', 'email', 'deleted_at')->withTrashed()]);
                        },
                        'user' => fn($u) => $u->select('id', 'name', 'last_name', 'email', 'avatar', 'deleted_at')->withTrashed(),
                        'user.userDetail:user_id,avatar_id,logo',
                        'model:id,name,brand_id',
                        'model.brand:id,name,logo', 
                        'state:id,name', 
                        'iva_purchase:id,name,value',
                        'iva_sale:id,name,value',
                        'currency_purchase:id,name,code',
                        'currency_sale:id,name,code',
                        'carbody:id,name',
                        'gearbox:id,name',
                        'fuel:id,name',
                        'client_purchase',                        
                        'client_purchase.client' => fn($q) => $q->select('id', 'fullname', 'email', 'deleted_at')->withTrashed(),
                        'client_sale.client' => fn($q) => $q->select('id', 'fullname', 'email', 'deleted_at')->withTrashed(),
                        'tasks'
                    ])->applyFilters(
                        $request->only([
                            'search',
                            'orderByField',
                            'orderBy',
                            'isSold',
                            'state_id',
                            'brand_id',
                            'model_id',
                            'year',
                            'gearbox_id',
                            'supplier_id',
                            'date_from',
                            'date_to'                            
                        ])
                    );

            if ($limit == -1) {
                $allVehicles = $query->get();
                $perPage = max(1, $allVehicles->count());
                $vehicles = new \Illuminate\Pagination\LengthAwarePaginator(
                    $allVehicles,
                    $allVehicles->count(),
                    $perPage,
                    1
                );
            } else {
                $vehicles = $query->paginate($limit);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'vehicles' => $vehicles,
                    'vehiclesTotalCount' => $vehicles->total(),
                    'brands' => CacheService::getBrands(),
                    'models' => CacheService::getCarModels(),
                    'gearboxes' => CacheService::getGearboxes(),
                    'suppliers' => CacheService::getActiveSuppliers()
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
    public function store(VehicleRequest $request)
    {
        try {
            $user = Auth::user();
            $name = $user->name . ' ' . $user->last_name;

            if ($request->documents && is_array($request->documents)) {
                foreach ($request->documents as $document) {
                    $image = $document['file'];
                    $originalName = $image->getClientOriginalName();

                    $path = 'vehicles/' . Str::slug($name) . '/';
                    $fullFilePath = $path . $originalName;

                    $existsInDatabase = VehicleDocument::where('file', $fullFilePath)->exists();
                    $existsInStorage = Storage::disk('public')->exists($fullFilePath);

                    if ($existsInDatabase || $existsInStorage) {
                        return response()->json([
                            'success' => false,
                            'response' => [
                                'data' => [
                                    'errors' => [
                                        'Dokumentet ' . $originalName . ' är redan sparat i systemet för ' . $name
                                        ]
                                ]
                            ],
                            'message' => 'Dokumentet ' . $originalName . ' är redan sparat i systemet för ' . $name
                        ],400);
                    }
                }
            }
            
            $vehicle = Vehicle::createVehicle($request);

            SupplierActivity::createActivity([
                'entity_id' => $vehicle->id,
                'entity_type' => 'vehicles',
                'action_type' => 'create_vehicle',
                'title' => 'Fordon '.$vehicle->reg_num.' skapad',
                'description' => 'Ett nytt fordon har skapats.',
                'icon' => 'custom-car',
                'route' => '/dashboard/admin/stock?vehicle_id='.$vehicle->id,
                'metadata' => json_encode([
                    'vehicle_id' => $vehicle->id,
                    'new_values' => $request->only([
                        'supplier_id', 'reg_num', 'year', 'generation',
                        'brand_id', 'model_id', 'model', 'car_body_id',
                        'fuel_id', 'gearbox_id', 'color', 'mileage',
                        'control_inspection', 'purchase_price', 'purchase_date', 'iva_purchase_id',
                        'currency_id', 'state_id', 'number_keys', 'service_book',
                        'summer_tire', 'winter_tire', 'last_service', 'last_service_date',
                        'dist_belt', 'last_dist_belt', 'last_dist_belt_date', 'comments',
                        'chassis', 'engine', 'car_name', 'iva_purchase_amount', 'iva_purchase_exclusive',
                        'type', 'client_id', 'tasks', 'documents'
                    ])
                ])
            ]);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'vehicle' => Vehicle::find($vehicle->id)
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

            $vehicle = Vehicle::with([
                'tasks.user.userDetail', 
                'tasks.comments.user.userDetail', 
                'tasks.histories.user.userDetail',
                'documents.user.userDetail',
                'documents.type',
                'client_purchase.client',
                'client_sale.client',
                'state'
            ])->find($id);

            if (!$vehicle)
                return response()->json([
                    'sucess' => false,
                    'feedback' => 'not_found',
                    'message' => 'Fordon hittades inte'
                ], 404);

            $clients = Client::when(
                Auth::check() && Auth::user()->hasRole('Supplier'), function ($query) {
                    return $query->where('supplier_id', Auth::user()->supplier->id);
                }
            )->when(
                Auth::check() && Auth::user()->hasRole('User'), function ($query) {
                    return $query->where('supplier_id', Auth::user()->supplier->boss_id);
                }
            )->withTrashed()->get();

            return response()->json([
                'success' => true,
                'data' => [ 
                    'vehicle' => $vehicle,
                    'brands' => CacheService::getBrands(),
                    'models' => CacheService::getCarModels(),
                    'carbodies' => CacheService::getCarBodies(),
                    'gearboxes' => CacheService::getGearboxes(),
                    'ivas' => CacheService::getIvas(),
                    'fuels' => CacheService::getFuels(),
                    'document_types' => CacheService::getDocumentTypes(),
                    'states' => CacheService::getVehicleStates(),
                    'clients' => $clients,
                    'client_types' => CacheService::getClientTypes(),
                    'identifications' => CacheService::getIdentifications(),
                    'currencies' => CacheService::getActiveCurrencies(),
                    'countries' => CacheService::getCountries()
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
    public function update(VehicleRequest $request, $id): JsonResponse
    {
        try {
            $vehicle = Vehicle::find($id);
        
            if (!$vehicle)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Fordon hittades inte'
                ], 404);

            $vehicleFields = [
                'supplier_id', 'reg_num', 'model_id', 'car_body_id',
                'gearbox_id', 'iva_purchase_id', 'currency_purchase_id', 'currency_sale_id',
                'fuel_id', 'state_id', 'mileage', 'generation',
                'year', 'control_inspection', 'color', 'purchase_price',
                'purchase_date', 'number_keys', 'service_book', 'summer_tire',
                'winter_tire', 'last_service', 'last_service_date', 'dist_belt',
                'last_dist_belt', 'last_dist_belt_date', 'comments', 'chassis',
                'engine', 'car_name', 'sale_price', 'sale_date',
                'iva_sale_id', 'sale_comments', 'iva_sale_amount', 'iva_sale_exclusive',
                'iva_purchase_amount', 'iva_purchase_exclusive', 'total_sale', 'discount',
                'registration_fee'
            ];

            $oldValues = $vehicle->only($vehicleFields);

            $vehicle->updateVehicle($request, $vehicle); 

            $newValues = $request->only($vehicleFields);

            SupplierActivity::createActivity([
                'entity_id' => $vehicle->id,
                'entity_type' => 'vehicles',
                'action_type' => 'update_vehicle',
                'title' => 'Fordon '.$vehicle->reg_num.' uppdaterad',
                'description' => 'Ett fordon har uppdaterats.',
                'icon' => 'custom-car',
                'route' => '/dashboard/admin/stock?vehicle_id='.$vehicle->id,
                'metadata' => json_encode([
                    'vehicle_id' => $vehicle->id,
                    'old_values' => $oldValues,
                    'new_values' => $newValues
                ])
            ]);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'vehicle' => $vehicle
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

            $vehicle = Vehicle::find($id);
        
            if (!$vehicle)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Fordon hittades inte'
                ], 404);

            $oldValues = $vehicle->only([
                'supplier_id', 'reg_num', 'state_id', 'model_id',
                'purchase_price', 'purchase_date', 'sale_price', 'sale_date'
            ]);

            SupplierActivity::createActivity([
                'entity_id' => $vehicle->id,
                'entity_type' => 'vehicles',
                'action_type' => 'delete_vehicle',
                'title' => 'Fordon '.$vehicle->reg_num.' borttagen',
                'description' => 'Ett fordon har tagits bort.',
                'icon' => 'custom-car',
                'metadata' => json_encode([
                    'vehicle_id' => $vehicle->id,
                    'old_values' => $oldValues
                ])
            ]);            

            SupplierActivity::where('entity_id', $vehicle->id)
                ->where('entity_type', 'vehicles')
                ->update(['route' => null]);

            $vehicle->deleteVehicle($id);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'vehicle' => $vehicle
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

    public function send(Request $request): JsonResponse
    {
        try {
            $vehicle = Vehicle::find($request->id);
        
            if (!$vehicle)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Fordon hittades inte'
                ], 404);

            $sendFields = [
                'state_id', 'chassis', 'sale_price', 'sale_date',
                'iva_sale_id', 'sale_comments', 'iva_sale_amount', 'iva_sale_exclusive',
                'iva_purchase_amount', 'iva_purchase_exclusive', 'total_sale', 'discount',
                'registration_fee'
            ];

            $oldValues = $vehicle->only($sendFields);

            $isSaleEdition = (int) $vehicle->state_id === 12;

            $vehicle = $vehicle->sendVehicle($request, $vehicle); 

            $newValues = $vehicle->only($sendFields);

            SupplierActivity::createActivity([
                'entity_id' => $vehicle->id,
                'entity_type' => 'vehicles',
                'action_type' => 'send_vehicle',
                'title' => $isSaleEdition
                    ? 'Fordon '.$vehicle->reg_num.' försäljning uppdaterad'
                    : 'Fordon '.$vehicle->reg_num.' såld',
                'description' => $isSaleEdition
                    ? 'En fordonsförsäljning har uppdaterats.'
                    : 'Ett fordon har sålts.',
                'icon' => 'custom-car',
                'route' => '/dashboard/admin/sold?vehicle_id='.$vehicle->id,
                'metadata' => json_encode([
                    'vehicle_id' => $vehicle->id,
                    'old_values' => $oldValues,
                    'new_values' => $newValues
                ])
            ]);

            SupplierActivity::where('entity_id', $vehicle->id)
                ->where('entity_type', 'vehicles')
                ->update(['route' => '/dashboard/admin/sold?vehicle_id='.$vehicle->id]);


            return response()->json([
                'success' => true,
                'data' => [ 
                    'vehicle' => $vehicle
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

    public function cancel($id): JsonResponse
    {
        try {
            $vehicle = Vehicle::find($id);
        
            if (!$vehicle)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Fordon hittades inte'
                ], 404);

            $cancelFields = [
                'state_id', 'sale_price', 'sale_date', 'iva_sale_id',
                'sale_comments', 'iva_sale_amount', 'iva_sale_exclusive',
                'total_sale', 'discount', 'registration_fee'
            ];

            $oldValues = $vehicle->only($cancelFields);

            $vehicle->cancelVehicle($vehicle); 

            $newValues = $vehicle->only($cancelFields);

            SupplierActivity::createActivity([
                'entity_id' => $vehicle->id,
                'entity_type' => 'vehicles',
                'action_type' => 'cancel_vehicle',
                'title' => 'Fordon '.$vehicle->reg_num.' avbruten försäljning',
                'description' => 'En fordonsförsäljning har avbrutits.',
                'icon' => 'custom-car',
                'route' => '/dashboard/admin/stock?vehicle_id='.$vehicle->id,
                'metadata' => json_encode([
                    'vehicle_id' => $vehicle->id,
                    'old_values' => $oldValues,
                    'new_values' => $newValues
                ])
            ]);

            SupplierActivity::where('entity_id', $vehicle->id)
                ->where('entity_type', 'vehicles')
                ->update(['route' => '/dashboard/admin/stock?vehicle_id='.$vehicle->id]);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'vehicle' => $vehicle
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

    public function findByRegNum(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $requestSupplierId = $request->input('supplier_id');

            if ($requestSupplierId === 'null' || $requestSupplierId === '' || $requestSupplierId === null) {
                $requestSupplierId = null;
            }

            $supplierId = match (true) {
                Auth::check() && $user->hasRole('Supplier') => optional($user->supplier)->id,
                Auth::check() && $user->hasRole('User') => optional($user->supplier)->boss_id,
                default => $requestSupplierId,
            };

            $vehicleQuery = Vehicle::where('reg_num', $request->regnum);

            if ($supplierId === null) {
                $vehicleQuery->whereNull('supplier_id');
            } else {
                $vehicleQuery->where('supplier_id', $supplierId);
            }

            $vehicle = $vehicleQuery->first();

            $clients = Client::when(
                Auth::check() && Auth::user()->hasRole('Supplier'), function ($query) {
                    return $query->where('supplier_id', Auth::user()->supplier->id);
                }
            )->when(
                Auth::check() && Auth::user()->hasRole('User'), function ($query) {
                    return $query->where('supplier_id', Auth::user()->supplier->boss_id);
                }
            )->get();
        
            if (!$vehicle)
                return response()->json([
                    'success' => false,
                    'data' => [
                        'message' => 'Fordon hittades inte',
                        'common_info' => [
                            'brands' => Brand::all(),
                            'models' => CarModel::with(['brand'])->get(),
                            'carbodies' => CarBody::all(),
                            'gearboxes' => Gearbox::all(),
                            'ivas' => Iva::all(),
                            'fuels' => Fuel::all(),
                            'document_types' => DocumentType::all(),
                            'states' => State::whereIn('id', [10, 11, 12, 13])->get(),
                            'clients' => $clients,
                            'client_types' => ClientType::all(),
                            'identifications' => Identification::all(),
                            'currencies' => Currency::where('state_id', 2)->get(),
                            'countries' => CacheService::getCountries()
                        ]
                    ]
                ], 200);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'vehicle' => $vehicle,
                    'common_info' => [
                        'brands' => Brand::all(),
                        'models' => CarModel::with(['brand'])->get(),
                        'carbodies' => CarBody::all(),
                        'gearboxes' => Gearbox::all(),
                        'ivas' => Iva::all(),
                        'fuels' => Fuel::all(),
                        'document_types' => DocumentType::all(),
                        'states' => State::whereIn('id', [10, 11, 12, 13])->get(),
                        'clients' => $clients,
                        'client_types' => ClientType::all(),
                        'identifications' => Identification::all(),
                        'currencies' => Currency::where('state_id', 2)->get()
                    ]
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
}
