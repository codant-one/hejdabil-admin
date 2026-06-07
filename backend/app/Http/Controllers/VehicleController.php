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
use App\Models\Notification;

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
            $activityFields = $this->getVehicleActivityFields();
            $activityNewValues = $this->getVehicleActivityValues($vehicle, $activityFields);

            SupplierActivity::createActivity([
                'entity_id' => $vehicle->id,
                'entity_type' => 'vehicles',
                'action_type' => 'create_vehicle',
                'title' => 'Fordon tillagt',
                'description' => 'Fordon '.$vehicle->reg_num.' har lagts till.',
                'icon' => 'custom-car',
                'route' => '/dashboard/admin/stock?vehicle_id='.$vehicle->id,
                'metadata' => json_encode([
                    'vehicle_id' => $vehicle->id,
                    'new_values' => $activityNewValues,
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
                'tasks.comments:id,vehicle_task_id,user_id,comment,created_at',
                'tasks.comments.user' => fn($u) => $u->select('id', 'name', 'last_name', 'avatar', 'deleted_at')->withTrashed(),
                'tasks.comments.user.userDetail:user_id,avatar_id,logo',
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
            $vehicle = Vehicle::with('model:id,brand_id')->find($id);
        
            if (!$vehicle)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Fordon hittades inte'
                ], 404);

            $vehicleFields = $this->getVehicleActivityFields();

            $oldValues = $this->getVehicleActivityValues($vehicle, $vehicleFields);

            $vehicle->updateVehicle($request, $vehicle);
            $vehicle->refresh()->load('model:id,brand_id');

            $newValues = $this->getVehicleActivityValues($vehicle, $vehicleFields);

            SupplierActivity::createActivity([
                'entity_id' => $vehicle->id,
                'entity_type' => 'vehicles',
                'action_type' => 'update_vehicle',
                'title' => 'Fordon uppdaterat',
                'description' => 'Fordon '.$vehicle->reg_num.' har uppdaterats.',
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

            $vehicle = Vehicle::with('model:id,brand_id')->find($id);
        
            if (!$vehicle)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Fordon hittades inte'
                ], 404);

            $oldValues = $this->getVehicleActivityValues($vehicle, $this->getVehicleActivityFields());

            $isSaleEdition = (int) $vehicle->state_id === 12;

            SupplierActivity::createActivity([
                'entity_id' => $vehicle->id,
                'entity_type' => 'vehicles',
                'action_type' => 'delete_vehicle',
                'title' => $isSaleEdition
                    ? 'Fordon '.$vehicle->reg_num.' - försäljning borttagen'
                    : 'Fordon borttaget',
                'description' => $isSaleEdition
                    ? 'Försäljningen har tagits bort.'
                    : 'Fordon '.$vehicle->reg_num.' har tagits bort.',
                'icon' => 'custom-car',
                'metadata' => json_encode([
                    'vehicle_id' => $vehicle->id,
                    'old_values' => $oldValues
                ])
            ]);            

            SupplierActivity::where('entity_id', $vehicle->id)
                ->where('entity_type', 'vehicles')
                ->update(['route' => null]);

            //Delete notifications related to the vehicle's stock edit page to prevent access to deleted vehicle through notifications
            Notification::deleteNotificationsByRoute('stock/edit/'.$id);

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

            $sendFields = $this->getVehicleActivityFields();

            $oldValues = $this->getVehicleActivityValues($vehicle, $sendFields);

            $isSaleEdition = (int) $vehicle->state_id === 12;

            $vehicle = $vehicle->sendVehicle($request, $vehicle); 

            $newValues = $this->getVehicleActivityValues($vehicle, $sendFields);

            SupplierActivity::createActivity([
                'entity_id' => $vehicle->id,
                'entity_type' => 'vehicles',
                'action_type' => 'sell_vehicle',
                'title' => $isSaleEdition
                    ? 'Fordon '.$vehicle->reg_num.' - försäljning uppdaterad'
                    : 'Fordon sålt',
                'description' => $isSaleEdition
                    ? 'Försäljningen har uppdaterats.'
                    : 'Fordon '.$vehicle->reg_num.' har sålts.',
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

            $cancelFields = $this->getVehicleActivityFields();

            $oldValues = $this->getVehicleActivityValues($vehicle, $cancelFields);

            $vehicle->cancelVehicle($vehicle); 

            $newValues = $this->getVehicleActivityValues($vehicle, $cancelFields);

            SupplierActivity::createActivity([
                'entity_id' => $vehicle->id,
                'entity_type' => 'vehicles',
                'action_type' => 'cancel_vehicle',
                'title' => 'Fordon '.$vehicle->reg_num.' - avbruten försäljning',
                'description' => 'Försäljningen har avbrutits.',
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

    public function getAgreements($id): JsonResponse
    {
        try {
            $vehicle = Vehicle::query()
                ->with('client_purchase.agreement', 'client_sale.agreement')
                ->withExists([
                    'client_purchase as has_purchase_agreement' => fn ($query) => $query->whereHas('agreement'),
                    'client_sale as has_sale_agreement' => fn ($query) => $query->whereHas('agreement'),
                ])
                ->find($id);
        
            if (!$vehicle)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Fordon hittades inte'
                ], 404);

            $purchaseAgreement = $vehicle->client_purchase?->agreement;
            $saleAgreement = $vehicle->client_sale?->agreement;

            return response()->json([
                'success' => true,
                'data' => [ 
                    'purchase_agreement' => $purchaseAgreement,
                    'sale_agreement' => $saleAgreement,
                    'has_purchase_agreement' => (bool) $vehicle->has_purchase_agreement,
                    'has_sale_agreement' => (bool) $vehicle->has_sale_agreement,
                    'has_agreement' => (bool) ($vehicle->has_purchase_agreement || $vehicle->has_sale_agreement)
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

    private function getVehicleActivityFields(): array
    {
        return [
            'supplier_id', 'reg_num', 'year', 'generation',
            'model_id', 'car_body_id', 'fuel_id', 'gearbox_id',
            'color', 'mileage', 'control_inspection', 'purchase_price',
            'purchase_date', 'iva_purchase_id', 'currency_purchase_id', 'currency_sale_id',
            'state_id', 'number_keys', 'service_book', 'summer_tire',
            'winter_tire', 'last_service', 'last_service_date', 'dist_belt',
            'last_dist_belt', 'last_dist_belt_date', 'comments', 'chassis',
            'engine', 'car_name', 'iva_purchase_amount', 'iva_purchase_exclusive',
            'sale_price', 'sale_date', 'iva_sale_id', 'sale_comments',
            'iva_sale_amount', 'iva_sale_exclusive', 'discount', 'registration_fee',
            'total_sale', 'costs', 'purchase_client', 'sale_client'
        ];
    }

    private function getVehicleActivityValues(Vehicle $vehicle, array $fields): array
    {
        $vehicle->load([
            'model:id,brand_id',
            'tasks',
            'client_purchase.client',
            'client_sale.client',
        ]);

        $values = $vehicle->only($fields);
        $values['brand_id'] = $vehicle->model?->brand_id;

        if (in_array('costs', $fields, true)) {
            $values['costs'] = $vehicle->tasks
                ->filter(fn ($task) => (int) $task->is_cost === 1)
                ->sum(fn ($task) => (float) $task->cost);
        }

        if (in_array('purchase_client', $fields, true))
            $values['purchase_client'] = $this->resolveVehicleClientLabel($vehicle->client_purchase);

        if (in_array('sale_client', $fields, true))
            $values['sale_client'] = $this->resolveVehicleClientLabel($vehicle->client_sale);

        return $values;
    }

    private function resolveVehicleClientLabel($vehicleClient): ?string
    {
        if (!$vehicleClient)
            return null;

        $fullname = trim((string) ($vehicleClient->fullname ?? ''));

        if ($fullname !== '')
            return $fullname;

        $relatedClientName = trim((string) ($vehicleClient->client?->fullname ?? ''));

        return $relatedClientName !== '' ? $relatedClientName : null;
    }
}
