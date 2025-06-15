<?php

namespace App\Http\Controllers;

use App\Http\Requests\BodyCarRequest;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

use Spatie\Permission\Middlewares\PermissionMiddleware;

use App\Models\BodysCar;

class BodysCarController extends Controller
{
    public function __construct()
    {
        $this->middleware(PermissionMiddleware::class . ':view bodyscar|administrator')->only(['index']);
        $this->middleware(PermissionMiddleware::class . ':create bodyscar|administrator')->only(['store']);
        $this->middleware(PermissionMiddleware::class . ':edit bodyscar|administrator')->only(['update']);
        $this->middleware(PermissionMiddleware::class . ':delete bodyscar|administrator')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $limit = $request->has('limit') ? $request->limit : 10;
        
            $query = BodysCar::applyFilters(
                                $request->only([
                                    'search',
                                    'orderByField',
                                    'orderBy'
                                ])
                            );

            $count = $query->count();

            $bodyscar = ($limit == -1) ? $query->paginate($query->count()) : $query->paginate($limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'bodyscar' => $bodyscar,
                    'bodyscarTotalCount' => $count
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
    public function store(BodyCarRequest $request)
    {
        try {

            $bodyscar = BodysCar::createObject($request);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'bodycar' => BodysCar::find($bodyscar->id)
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

            $bodycar = BodysCar::find($id);

            if (!$bodycar)
                return response()->json([
                    'sucess' => false,
                    'feedback' => 'not_found',
                    'message' => 'Kaross hittades inte'
                ], 404);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'bodycar' => $bodycar
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
    public function update(BodyCarRequest $request, $id): JsonResponse
    {
        try {
            $bodycar = BodysCar::find($id);
        
            if (!$bodycar)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Kaross hittades inte'
                ], 404);

            $bodycar->updateObject($request, $bodycar); 

            return response()->json([
                'success' => true,
                'data' => [ 
                    'bodycar' => $bodycar
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

            $bodycar = BodysCar::find($id);
        
            if (!$bodycar)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Kaross hittades inte'
                ], 404);
            
            $bodycar->deleteObject($id);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'bodycar' => $bodycar
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
