<?php

namespace App\Http\Controllers;

use App\Http\Requests\GearboxRequest;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

use Spatie\Permission\Middlewares\PermissionMiddleware;

use App\Models\Gearbox;

class GearboxController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $limit = $request->has('limit') ? $request->limit : 10;
        
            $query = Gearbox::applyFilters(
                                $request->only([
                                    'search',
                                    'orderByField',
                                    'orderBy'
                                ])
                            );

            $count = $query->count();

            $gearboxes = ($limit == -1) ? $query->paginate($query->count()) : $query->paginate($limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'gearboxes' => $gearbox,
                    'gearboxesTotalCount' => $count
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
    public function store(GearboxRequest $request)
    {
        try {

            $gearbox = Gearbox::createGearbox($request);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'gearbox' => Gearbox::find($gearbox->id)
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

            $gearbox = Gearbox::find($id);

            if (!$gearbox)
                return response()->json([
                    'sucess' => false,
                    'feedback' => 'not_found',
                    'message' => 'Växellåda hittades inte'
                ], 404);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'gearbox' => $gearbox
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
    public function update(GearboxRequest $request, $id): JsonResponse
    {
        try {
            $gearbox = Gearbox::find($id);
        
            if (!$gearbox)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Växellåda hittades inte'
                ], 404);

            $gearbox->updateGearbox($request, $gearbox); 

            return response()->json([
                'success' => true,
                'data' => [ 
                    'gearbox' => $gearbox
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

            $gearbox = Gearbox::find($id);
        
            if (!$gearbox)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Växellåda hittades inte'
                ], 404);
            
            $gearbox->deleteGearbox($id);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'gearbox' => $gearbox
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
