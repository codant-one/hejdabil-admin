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
use App\Models\State;
use App\Models\Client;

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
        
            $query = Vehicle::with(['model.brand', 'state', 'iva'])
                        ->applyFilters(
                                $request->only([
                                    'search',
                                    'orderByField',
                                    'orderBy'
                                ])
                            );

            $count = $query->count();

            $vehicles = ($limit == -1) ? $query->paginate($query->count()) : $query->paginate($limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'vehicles' => $vehicles,
                    'vehiclesTotalCount' => $count
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
                'tasks.histories',
                'costs',
                'documents.user',
                'documents.type'
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
                $clients = Client::all();
            }

            return response()->json([
                'success' => true,
                'data' => [ 
                    'vehicle' => $vehicle,
                    'brands' => Brand::all(),
                    'models' => CarModel::with(['brand'])->get(),
                    'carbodies' => CarBody::all(),
                    'gearboxes' => Gearbox::all(),
                    'ivas' => Iva::all(),
                    'states' => State::whereIn('id', [10, 11, 12, 13])->get(),
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
    public function destroy(Vehicle $vehicle)
    {
        //
    }
}
