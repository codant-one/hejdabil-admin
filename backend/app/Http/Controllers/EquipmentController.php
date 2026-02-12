<?php

namespace App\Http\Controllers;

use App\Http\Requests\EquipmentRequest;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

use Spatie\Permission\Middlewares\PermissionMiddleware;

use App\Models\Equipment;

class EquipmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $limit = $request->has('limit') ? $request->limit : 10;
        
            $query = Equipment::applyFilters(
                                $request->only([
                                    'search',
                                    'orderByField',
                                    'orderBy'
                                ])
                            );

            if ($limit == -1) {
                $allEquipments = $query->get();
                $equipments = new \Illuminate\Pagination\LengthAwarePaginator(
                    $allEquipments,
                    $allEquipments->count(),
                    $allEquipments->count(),
                    1
                );
            } else {
                $equipments = $query->paginate($limit);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'equipments' => $equipments,
                    'equipmentsTotalCount' => $equipments->total()
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
    public function store(EquipmentRequest $request)
    {
        try {

            $equipment = Equipment::createEquipment($request);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'equipment' => Equipment::find($equipment->id)
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

            $equipment = Equipment::find($id);

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
    public function update(EquipmentRequest $request, $id): JsonResponse
    {
        try {
            $equipment = Equipment::find($id);
        
            if (!$equipment)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Utrustning hittades inte'
                ], 404);

            $equipment->updateEquipment($request, $equipment); 

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

            $equipment = Equipment::find($id);
        
            if (!$equipment)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Utrustning hittades inte'
                ], 404);
            
            $equipment->deleteEquipments($id);

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
