<?php

namespace App\Http\Controllers;

use App\Http\Requests\CarModelRequest;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

use Spatie\Permission\Middlewares\PermissionMiddleware;

use App\Models\CarModel;

class CarModelController extends Controller
{
    public function __construct()
    {
        $this->middleware(PermissionMiddleware::class . ':view models|administrator')->only(['index']);
        $this->middleware(PermissionMiddleware::class . ':create models|administrator')->only(['store']);
        $this->middleware(PermissionMiddleware::class . ':edit models|administrator')->only(['update']);
        $this->middleware(PermissionMiddleware::class . ':delete models|administrator')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $limit = $request->has('limit') ? $request->limit : 10;
        
            $query = CarModel::with(['brand'])
                           ->applyFilters(
                                $request->only([
                                    'search',
                                    'orderByField',
                                    'orderBy'
                                ])
                            );

            $count = $query->count();

            $modelscar = ($limit == -1) ? $query->paginate($query->count()) : $query->paginate($limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'modelscar' => $modelscar,
                    'modelscarTotalCount' => $count
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
    public function store(CarModelRequest $request)
    {
        try {

            $CarModel = CarModel::createObject($request);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'CarModel' => CarModel::with(['brand'])->find($CarModel->id)
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

            $CarModel = CarModel::with(['brand'])->find($id);

            if (!$CarModel)
                return response()->json([
                    'sucess' => false,
                    'feedback' => 'not_found',
                    'message' => 'Modell hittades inte'
                ], 404);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'CarModel' => $CarModel
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
    public function update(CarModelRequest $request, $id): JsonResponse
    {
        try {
            $CarModel = CarModel::with(['brand'])->find($id);
        
            if (!$CarModel)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Modell hittades inte'
                ], 404);

            $CarModel->updateObject($request, $CarModel); 

            return response()->json([
                'success' => true,
                'data' => [ 
                    'CarModel' => $CarModel
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

            $CarModel = CarModel::find($id);
        
            if (!$CarModel)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Modell hittades inte'
                ], 404);
            
            $CarModel->deleteObject($id);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'CarModel' => $CarModel
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
