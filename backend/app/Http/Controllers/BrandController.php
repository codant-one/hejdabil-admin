<?php

namespace App\Http\Controllers;

use App\Http\Requests\BrandRequest;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

use Spatie\Permission\Middlewares\PermissionMiddleware;

use App\Models\Brand;

class BrandController extends Controller
{
    public function __construct()
    {
        $this->middleware(PermissionMiddleware::class . ':view brands|administrator')->only(['index']);
        $this->middleware(PermissionMiddleware::class . ':create brands|administrator')->only(['store']);
        $this->middleware(PermissionMiddleware::class . ':edit brands|administrator')->only(['update']);
        $this->middleware(PermissionMiddleware::class . ':delete brands|administrator')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $limit = $request->has('limit') ? $request->limit : 10;
        
            $query = Brand::applyFilters(
                                $request->only([
                                    'search',
                                    'orderByField',
                                    'orderBy'
                                ])
                            );

            $count = $query->count();

            $brands = ($limit == -1) ? $query->paginate($query->count()) : $query->paginate($limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'brands' => $brands,
                    'brandsTotalCount' => $count
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
    public function store(BrandRequest $request)
    {
        try {

            $brand = Brand::createObject($request);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'brand' => Brand::find($brand->id)
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

            $brand = Brand::find($id);

            if (!$brand)
                return response()->json([
                    'sucess' => false,
                    'feedback' => 'not_found',
                    'message' => 'Märke hittades inte'
                ], 404);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'brand' => $brand
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
    public function update(BrandRequest $request, $id): JsonResponse
    {
        try {
            $brand = Brand::find($id);
        
            if (!$brand)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Märke hittades inte'
                ], 404);

            $brand->updateObject($request, $brand); 

            return response()->json([
                'success' => true,
                'data' => [ 
                    'brand' => $brand
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

            $brand = Brand::find($id);
        
            if (!$brand)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Märke hittades inte'
                ], 404);
            
            $brand->deleteObject($id);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'brand' => $brand
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
