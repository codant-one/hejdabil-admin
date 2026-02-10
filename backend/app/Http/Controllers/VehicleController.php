<?php

namespace App\Http\Controllers;

use App\Http\Requests\VehicleRequest;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
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

            $limit = $request->has('limit') ? $request->limit : 10;
        
            $query = Vehicle::with([
                        'supplier' => function ($q) {
                            $q->select('id', 'user_id', 'boss_id', 'deleted_at')
                              ->withTrashed()
                              ->with(['user' => fn($u) => $u->select('id', 'name', 'last_name', 'email', 'deleted_at')->withTrashed()]);
                        },
                        'user:id,name,last_name,email,avatar',
                        'user.userDetail:user_id,logo',
                        'model:id,name,brand_id',
                        'model.brand:id,name', 
                        'state:id,name', 
                        'iva_purchase:id,name,value',
                        'iva_sale:id,name,value',
                        'currency_purchase:id,name,code',
                        'currency_sale:id,name,code',
                        'carbody:id,name',
                        'gearbox:id,name',
                        'fuel:id,name',
                        'client_purchase',
                        'client_purchase.client:id,fullname,email',
                        'client_sale',
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
                            'supplier_id'
                        ])
                    );

            if ($limit == -1) {
                $allVehicles = $query->get();
                $vehicles = new \Illuminate\Pagination\LengthAwarePaginator(
                    $allVehicles,
                    $allVehicles->count(),
                    $allVehicles->count(),
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

            $vehicle = Vehicle::createVehicle($request);

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
                'tasks.user', 
                'tasks.comments.user', 
                'tasks.histories.user',
                'documents.user',
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

            if (Auth::user()->getRoleNames()[0] === 'Supplier') {
                $clients = Client::where('supplier_id', Auth::user()->supplier->id)->get();
            } else {
                $clients = CacheService::getClients();
            }

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
                    'currencies' => CacheService::getActiveCurrencies()
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
            $vehicle = Vehicle::find($id);
        
            if (!$vehicle)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Fordon hittades inte'
                ], 404);

            $vehicle->updateVehicle($request, $vehicle); 

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

            $vehicle->sendVehicle($request, $vehicle); 

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
}
