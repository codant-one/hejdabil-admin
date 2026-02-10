<?php

namespace App\Http\Controllers;

use App\Http\Requests\IvaRequest;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

use Spatie\Permission\Middlewares\PermissionMiddleware;

use App\Models\Iva;

class IvaController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $limit = $request->has('limit') ? $request->limit : 10;
        
            $query = Iva::applyFilters(
                                $request->only([
                                    'search',
                                    'orderByField',
                                    'orderBy'
                                ])
                            );

            if ($limit == -1) {
                $allIvas = $query->get();
                $ivas = new \Illuminate\Pagination\LengthAwarePaginator(
                    $allIvas,
                    $allIvas->count(),
                    $allIvas->count(),
                    1
                );
            } else {
                $ivas = $query->paginate($limit);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'ivas' => $ivas,
                    'ivasTotalCount' => $ivas->total()
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
    public function store(IvaRequest $request)
    {
        try {

            $iva = Iva::createIva($request);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'iva' => Iva::find($iva->id)
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

            $iva = Iva::find($id);

            if (!$iva)
                return response()->json([
                    'sucess' => false,
                    'feedback' => 'not_found',
                    'message' => 'Moms hittades inte'
                ], 404);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'iva' => $iva
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
    public function update(IvaRequest $request, $id): JsonResponse
    {
        try {
            $iva = Iva::find($id);
        
            if (!$iva)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Moms hittades inte'
                ], 404);

            $iva->updateIva($request, $iva); 

            return response()->json([
                'success' => true,
                'data' => [ 
                    'iva' => $iva
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

            $iva = Iva::find($id);
        
            if (!$iva)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Moms hittades inte'
                ], 404);
            
            $iva->deleteIva($id);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'iva' => $iva
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
