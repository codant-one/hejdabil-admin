<?php

namespace App\Http\Controllers;

use App\Http\Requests\CurrencyRequest;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

use Spatie\Permission\Middlewares\PermissionMiddleware;

use App\Models\Currency;

class CurrencyController extends Controller
{
    public function __construct()
    {
        $this->middleware(PermissionMiddleware::class . ':view currencies|administrator')->only(['index']);
        $this->middleware(PermissionMiddleware::class . ':create currencies|administrator')->only(['store']);
        $this->middleware(PermissionMiddleware::class . ':edit currencies|administrator')->only(['update']);
        $this->middleware(PermissionMiddleware::class . ':delete currencies|administrator')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $limit = $request->has('limit') ? $request->limit : 10;
        
            $query = Currency::with(['state'])
                            ->applyFilters(
                                $request->only([
                                    'search',
                                    'orderByField',
                                    'orderBy',
                                    'state_id'
                                ])
                            );

            $count = $query->count();

            $currencies = ($limit == -1) ? $query->paginate($query->count()) : $query->paginate($limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'currencies' => $currencies,
                    'currenciesTotalCount' => $count
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
    public function store(CurrencyRequest $request)
    {
        try {

            $currency = Currency::createCurrency($request);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'currency' => Currency::find($currency->id)
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

            $currency = Currency::find($id);

            if (!$currency)
                return response()->json([
                    'sucess' => false,
                    'feedback' => 'not_found',
                    'message' => 'Valuta hittades inte'
                ], 404);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'currency' => $currency
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
    public function update(CurrencyRequest $request, $id): JsonResponse
    {
        try {
            $currency = Currency::find($id);
        
            if (!$currency)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Valuta hittades inte'
                ], 404);

            $currency->updateCurrency($request, $currency); 
            
            return response()->json([
                'success' => true,
                'data' => [ 
                    'currency' => $currency
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

            $currency = Currency::find($id);
        
            if (!$currency)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Valuta hittades inte'
                ], 404);
            
            $currency->deleteCurrency($id);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'currency' => $currency
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

    public function updateState($id)
    {
        try {

            $currency = Currency::find($id);
        
            if (!$currency)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Valuta hittades inte'
                ], 404);
            
            $currency->state_id = $currency->state_id === 1 ? 2 : 1;
            $currency->update();

            return response()->json([
                'success' => true,
                'data' => [ 
                    'currency' => $currency
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
