<?php

namespace App\Http\Controllers;

use App\Http\Requests\ModelRequest;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

use Spatie\Permission\Middlewares\PermissionMiddleware;

use App\Models\CarModel;
use App\Models\SupplierActivity;

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
        
            $query = CarModel::with(['brand:id,name,url,logo'])
                           ->applyFilters(
                                $request->only([
                                    'search',
                                    'orderByField',
                                    'orderBy',
                                    'brand_id'
                                ])
                            );

            if ($limit == -1) {
                $allModels = $query->get();
                $models = new \Illuminate\Pagination\LengthAwarePaginator(
                    $allModels,
                    $allModels->count(),
                    max($allModels->count(), 1),
                    1
                );
            } else {
                $models = $query->paginate($limit);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'models' => $models,
                    'modelsTotalCount' => $models->total()
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

            SupplierActivity::createActivity([
                'entity_id' => $model->id,
                'entity_type' => 'models',
                'action_type' => 'create_model',
                'title' => 'Modell #'.$model->id.' '.$model->name.' tillagd',
                'description' => 'En ny modell har lagts till.',
                'icon' => 'custom-car-model',
                'route' => null, //'/dashboard/admin/models/'.$model->id,
                'metadata' => json_encode([
                    'model_id' => $model->id,
                    'new_values' => $request->only(['name', 'brand_id'])
                ])
            ]);

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

            $oldValues = $model->only(['name', 'brand_id']); 

            $model->updateModel($request, $model); 

            SupplierActivity::createActivity([
                'entity_id' => $model->id,
                'entity_type' => 'models',
                'action_type' => 'update_model',
                'title' => 'Modell #'.$model->id.' '.$model->name.' uppdaterad',
                'description' => 'En modell har uppdaterats.',
                'icon' => 'custom-car-model',
                'route' => null, //'/dashboard/admin/models/'.$model->id,
                'metadata' => json_encode([
                    'model_id' => $model->id,
                    'old_values' => $oldValues,
                    'new_values' => $request->only(['name', 'brand_id'])
                ])
            ]);

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

            $fields = [
                'name', 
                'brand_id'
            ];

            $oldValues = $model->only($fields);

            SupplierActivity::createActivity([
                'entity_id' => $model->id,
                'entity_type' => 'models',
                'action_type' => 'delete_model',
                'title' => 'Modell #'.$model->id.' '.$model->name.' borttagen',
                'description' => 'Modellen har tagits bort.',
                'icon' => 'custom-auto-2-ilager',
                'metadata' => json_encode([
                    'model_id' => $model->id,
                    'old_values' => $oldValues
                ])
            ]);

            SupplierActivity::where('entity_id', $model->id)
                ->where('entity_type', 'models')
                ->update(['route' => null]);

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
