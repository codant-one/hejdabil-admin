<?php

namespace App\Http\Controllers;

use App\Http\Requests\ModelRequest;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

use Spatie\Permission\Middlewares\PermissionMiddleware;

use App\Models\CarModel;

class ModelController extends Controller
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
                                    'orderBy',
                                    'brand_id'
                                ])
                            );

            $count = $query->count();

            $models = ($limit == -1) ? $query->paginate($query->count()) : $query->paginate($limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'models' => $models,
                    'modelsTotalCount' => $count
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
    public function store(ModelRequest $request)
    {
        try {

            $model = CarModel::createModel($request);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'model' => CarModel::with(['brand'])->find($model->id)
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

            $model = CarModel::with(['brand'])->find($id);

            if (!$model)
                return response()->json([
                    'sucess' => false,
                    'feedback' => 'not_found',
                    'message' => 'Modell hittades inte'
                ], 404);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'model' => $model
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
    public function update(ModelRequest $request, $id): JsonResponse
    {
        try {
            $model = CarModel::with(['brand'])->find($id);
        
            if (!$model)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Modell hittades inte'
                ], 404);

            $model->updateModel($request, $model); 

            return response()->json([
                'success' => true,
                'data' => [ 
                    'model' => $model
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

            $model = CarModel::find($id);
        
            if (!$model)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Modell hittades inte'
                ], 404);
            
            $model->deleteModel($id);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'model' => $model
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
