<?php

namespace App\Http\Controllers;

use App\Http\Requests\ModelCarRequest;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

use Spatie\Permission\Middlewares\PermissionMiddleware;

use App\Models\ModelCar;

class ModelCarController extends Controller
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
        
            $query = ModelCar::with(['brand'])
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
    public function store(ModelCarRequest $request)
    {
        try {

            $modelcar = ModelCar::createObject($request);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'modelcar' => ModelCar::with(['brand'])->find($modelcar->id)
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

            $modelcar = ModelCar::with(['brand'])->find($id);

            if (!$modelcar)
                return response()->json([
                    'sucess' => false,
                    'feedback' => 'not_found',
                    'message' => 'Modell hittades inte'
                ], 404);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'modelcar' => $modelcar
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
    public function update(ModelCarRequest $request, $id): JsonResponse
    {
        try {
            $modelcar = ModelCar::with(['brand'])->find($id);
        
            if (!$modelcar)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Modell hittades inte'
                ], 404);

            $modelcar->updateObject($request, $modelcar); 

            return response()->json([
                'success' => true,
                'data' => [ 
                    'modelcar' => $modelcar
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

            $modelcar = ModelCar::find($id);
        
            if (!$modelcar)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Modell hittades inte'
                ], 404);
            
            $modelcar->deleteObject($id);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'modelcar' => $modelcar
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
