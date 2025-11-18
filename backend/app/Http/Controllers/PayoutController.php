<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use Spatie\Permission\Middlewares\PermissionMiddleware;

use App\Models\Payout;

class PayoutController extends Controller
{
    public function __construct()
    {
        $this->middleware(PermissionMiddleware::class . ':view payouts|administrator')->only(['index']);
        $this->middleware(PermissionMiddleware::class . ':create payouts|administrator')->only(['store']);
        $this->middleware(PermissionMiddleware::class . ':edit payouts|administrator')->only(['update']);
        $this->middleware(PermissionMiddleware::class . ':delete payouts|administrator')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $limit = $request->has('limit') ? $request->limit : 10;
        
            $query = Payout::with(['state', 'user'])
                           ->applyFilters(
                                $request->only([
                                    'search',
                                    'orderByField',
                                    'orderBy',
                                    'user_id'
                                ])
                            );

            $count = $query->count();

            $payouts = ($limit == -1) ? $query->paginate($query->count()) : $query->paginate($limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'payouts' => $payouts,
                    'payoutsTotalCount' => $count
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
    public function store(Request $request)
    {
      //
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {

            $payout = Payout::with(['state', 'user'])->find($id);

            if (!$payout)
                return response()->json([
                    'sucess' => false,
                    'feedback' => 'not_found',
                    'message' => 'Betalningen hittades inte'
                ], 404);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'payout' => $payout
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
    public function update(Request $request, $id): JsonResponse
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {

            $payout = Payout::find($id);
        
            if (!$payout)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Betalningen hittades inte'
                ], 404);
            
            $payout->deletePayout($id);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'payout' => $payout
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
