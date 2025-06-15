<?php

namespace App\Http\Controllers;

use App\Http\Requests\EquipmentsListRequest;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

use Spatie\Permission\Middlewares\PermissionMiddleware;

use App\Models\EquipmentsList;

class EquipmentsListController extends Controller
{
    public function __construct()
    {
        $this->middleware(PermissionMiddleware::class . ':view equipmentslist|administrator')->only(['index']);
        $this->middleware(PermissionMiddleware::class . ':create equipmentslist|administrator')->only(['store']);
        $this->middleware(PermissionMiddleware::class . ':edit equipmentslist|administrator')->only(['update']);
        $this->middleware(PermissionMiddleware::class . ':delete equipmentslist|administrator')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $limit = $request->has('limit') ? $request->limit : 10;
        
            $query = EquipmentsList::applyFilters(
                                $request->only([
                                    'search',
                                    'orderByField',
                                    'orderBy'
                                ])
                            );

            $count = $query->count();

            $equipmentsList = ($limit == -1) ? $query->paginate($query->count()) : $query->paginate($limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'equipmentsList' => $equipmentsList,
                    'equipmentsListTotalCount' => $count
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
    public function store(EquipmentsListRequest $request)
    {
        try {

            $equipment = EquipmentsList::createObject($request);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'equipment' => EquipmentsList::find($equipment->id)
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

            $equipment = EquipmentsList::find($id);

            if (!$equipment)
                return response()->json([
                    'sucess' => false,
                    'feedback' => 'not_found',
                    'message' => 'Utrustning hittades inte'
                ], 404);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'equipment' => $equipment
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
    public function update(EquipmentsListRequest $request, $id): JsonResponse
    {
        try {
            $equipment = EquipmentsList::find($id);
        
            if (!$equipment)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Utrustning hittades inte'
                ], 404);

            $equipment->updateObject($request, $equipment); 

            return response()->json([
                'success' => true,
                'data' => [ 
                    'equipment' => $equipment
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

            $equipment = EquipmentsList::find($id);
        
            if (!$equipment)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Utrustning hittades inte'
                ], 404);
            
            $equipment->deleteObject($id);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'equipment' => $equipment
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
