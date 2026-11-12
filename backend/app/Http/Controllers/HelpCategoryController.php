<?php

namespace App\Http\Controllers;

use App\Http\Requests\HelpCategoryRequest;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

use Spatie\Permission\Middlewares\PermissionMiddleware;

use App\Models\HelpCategory;

class HelpCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware(PermissionMiddleware::class . ':view help-categories|administrator')->only(['index']);
        $this->middleware(PermissionMiddleware::class . ':create help-categories|administrator')->only(['store']);
        $this->middleware(PermissionMiddleware::class . ':edit help-categories|administrator')->only(['update', 'updateState']);
        $this->middleware(PermissionMiddleware::class . ':delete help-categories|administrator')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $limit = $request->has('limit') ? $request->limit : 10;
        
            $query = HelpCategory::with(['state:id,name'])->applyFilters(
                                $request->only([
                                    'search',
                                    'orderByField',
                                    'orderBy',
                                    'state_id'
                                ])
                            );

            if ($limit == -1) {
                $allHelpCategories = $query->get();
                $helpCategories = new \Illuminate\Pagination\LengthAwarePaginator(
                    $allHelpCategories,
                    $allHelpCategories->count(),
                    max($allHelpCategories->count(), 1),
                    1
                );
            } else {
                $helpCategories = $query->paginate($limit);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'helpCategories' => $helpCategories,
                    'helpCategoriesTotalCount' => $helpCategories->total()
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
    public function store(HelpCategoryRequest $request)
    {
        try {

            $helpCategory = HelpCategory::createHelpCategory($request);

            if ($request->hasFile('logo')) {
                $image = $request->file('logo');

                $path = 'help-categories/';

                $file_data = uploadFile($image, $path);

                $helpCategory->icon = $file_data['filePath'];
                $helpCategory->update();
            } 

            return response()->json([
                'success' => true,
                'data' => [ 
                    'helpCategory' => HelpCategory::find($helpCategory->id)
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

            $helpCategory = HelpCategory::find($id);

            if (!$helpCategory)
                return response()->json([
                    'sucess' => false,
                    'feedback' => 'not_found',
                    'message' => 'Kategori hittades inte'
                ], 404);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'helpCategory' => $helpCategory
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
    public function update(HelpCategoryRequest $request, $id): JsonResponse
    {
        try {
            $helpCategory = HelpCategory::find($id);
        
            if (!$helpCategory)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Kategori hittades inte'
                ], 404);

            $helpCategory->updateHelpCategory($request, $helpCategory); 

            if ($request->hasFile('logo')) {
                $image = $request->file('logo');

                $path = 'help-categories/';

                $file_data = uploadFile($image, $path, $helpCategory->icon);

                $helpCategory->icon = $file_data['filePath'];
                $helpCategory->update();
            } 
            
            return response()->json([
                'success' => true,
                'data' => [ 
                    'helpCategory' => $helpCategory
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

            $helpCategory = HelpCategory::find($id);
        
            if (!$helpCategory)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Kategori hittades inte'
                ], 404);
            
            $helpCategory->deleteHelpCategory($id);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'helpCategory' => $helpCategory
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

    public function updateState($id): JsonResponse
    {
        try {
            $helpCategory = HelpCategory::find($id);

            if (!$helpCategory) {
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Kategori hittades inte'
                ], 404);
            }

            $helpCategory->state_id = $helpCategory->state_id === 1 ? 2 : 1;
            $helpCategory->update();

            return response()->json([
                'success' => true,
                'data' => [
                    'helpCategory' => $helpCategory->load('state:id,name')
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
