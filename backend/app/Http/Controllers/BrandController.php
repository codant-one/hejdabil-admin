<?php

namespace App\Http\Controllers;

use App\Http\Requests\BrandRequest;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

use Spatie\Permission\Middlewares\PermissionMiddleware;

use App\Models\Brand;
use App\Models\SupplierActivity;

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

            if ($limit == -1) {
                $allBrands = $query->get();
                $brands = new \Illuminate\Pagination\LengthAwarePaginator(
                    $allBrands,
                    $allBrands->count(),
                    max($allBrands->count(), 1),
                    1
                );
            } else {
                $brands = $query->paginate($limit);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'brands' => $brands,
                    'brandsTotalCount' => $brands->total()
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

            $brand = Brand::createBrand($request);
            $fields = [
                'name', 'url'
            ];

            $new_values = $request->only($fields);

            if ($request->hasFile('logo')) {
                $image = $request->file('logo');

                $path = 'brands/';

                $file_data = uploadFile($image, $path);

                $brand->logo = $file_data['filePath'];
                $brand->update();

                $new_values['logo'] = $file_data['filePath'];
            } 
            
            SupplierActivity::createActivity([
                'entity_id' => $brand->id,
                'entity_type' => 'brands',
                'action_type' => 'create_brand',
                'title' => 'Märke #'.$brand->id.' '.$brand->name.' tillagd',
                'description' => 'Ett nytt märke har lagts till.',
                'icon' => 'custom-auto-2-ilager',
                'route' => '/dashboard/admin/brands/'.$brand->id,
                'metadata' => json_encode([
                    'brand_id' => $brand->id,
                    'new_values' => $new_values
                ])
            ]);

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

            $fields = [
                'name',
                'url'
            ];
            $new_values = $request->only($fields);

            $fields[] = 'logo';
            $oldValues = $brand->only($fields);

            $brand->updateBrand($request, $brand); 

            if ($request->hasFile('logo')) {
                $image = $request->file('logo');

                $path = 'brands/';

                $file_data = uploadFile($image, $path, $brand->logo);

                $brand->logo = $file_data['filePath'];
                $brand->update();

                $new_values['logo'] = $file_data['filePath'];
            } 

            SupplierActivity::createActivity([
                'entity_id' => $brand->id,
                'entity_type' => 'brands',
                'action_type' => 'update_brand',
                'title' => 'Märke #'.$brand->id.' '.$brand->name.' uppdaterad',
                'description' => 'Ett märke har uppdaterats.',
                'icon' => 'custom-auto-2-ilager',
                'route' => '/dashboard/admin/brands/'.$brand->id,
                'metadata' => json_encode([
                    'brand_id' => $brand->id,
                    'old_values' => $oldValues,
                    'new_values' => $new_values
                ])
            ]);
            
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
            
            $brand->deleteBrand($id);

            $fields = [
                'name',
                'url',
                'logo'
            ];

            $oldValues = $brand->only($fields);

            SupplierActivity::createActivity([
                'entity_id' => $brand->id,
                'entity_type' => 'brands',
                'action_type' => 'delete_brand',
                'title' => 'Märke #'.$brand->id.' '.$brand->name.' borttagen',
                'description' => 'Märket har tagits bort.',
                'icon' => 'custom-auto-2-ilager',
                'metadata' => json_encode([
                    'brand_id' => $brand->id,
                    'old_values' => $oldValues
                ])
            ]);

            SupplierActivity::where('entity_id', $brand->id)
                ->where('entity_type', 'brands')
                ->update(['route' => null]);

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
