<?php

namespace App\Http\Controllers;

use App\Http\Requests\CarBodyRequest;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

use Spatie\Permission\Middlewares\PermissionMiddleware;

use App\Models\CarBody;

class CarBodyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $limit = $request->has('limit') ? $request->limit : 10;
        
            $query = CarBody::applyFilters(
                                $request->only([
                                    'search',
                                    'orderByField',
                                    'orderBy'
                                ])
                            );

            $count = $query->count();

            $carbodies = ($limit == -1) ? $query->paginate($query->count()) : $query->paginate($limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'carbodies' => $carbodies,
                    'carbodiesTotalCount' => $count
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
    public function store(CarBodyRequest $request)
    {
        try {

            $carbody = CarBody::createCarBody($request);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'carbody' => CarBody::find($carbody->id)
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

            $carbody = CarBody::find($id);

            if (!$carbody)
                return response()->json([
                    'sucess' => false,
                    'feedback' => 'not_found',
                    'message' => 'Kaross hittades inte'
                ], 404);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'carbody' => $carbody
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
    public function update(CarBodyRequest $request, $id): JsonResponse
    {
        try {
            $carbody = CarBody::find($id);
        
            if (!$carbody)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Kaross hittades inte'
                ], 404);

            $carbody->updateCarbody($request, $carbody); 

            return response()->json([
                'success' => true,
                'data' => [ 
                    'carbody' => $carbody
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

            $carbody = CarBody::find($id);
        
            if (!$carbody)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Kaross hittades inte'
                ], 404);
            
            $carbody->deleteObject($id);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'carbody' => $carbody
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
