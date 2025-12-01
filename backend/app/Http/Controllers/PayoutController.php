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

            // Agregar información adicional calculada
            $payoutData = $payout->toArray();
            
            // Información de timestamps formateados
            $payoutData['created_at_formatted'] = $payout->created_at?->format('Y-m-d H:i:s');
            $payoutData['updated_at_formatted'] = $payout->updated_at?->format('Y-m-d H:i:s');
            
            // Información del estado actual
            $payoutData['has_error'] = !empty($payout->error_message);
            $payoutData['is_completed'] = in_array($payout->status, ['PAID', 'CANCELLED']);
            $payoutData['is_pending'] = in_array($payout->status, ['CREATED', 'DEBITED']);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'payout' => $payoutData
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
        try {
            $payout = Payout::find($id);
        
            if (!$payout) {
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Betalningen hittades inte'
                ], 404);
            }

            // Validar el estado si se proporciona
            if ($request->has('status')) {
                $validStatuses = ['CREATED', 'DEBITED', 'PAID', 'ERROR', 'CANCELLED'];
                
                if (!in_array($request->status, $validStatuses)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid status. Valid statuses: ' . implode(', ', $validStatuses)
                    ], 422);
                }
                
                $payout->status = $request->status;
            }

            // Actualizar campos específicos de Swish si se proporcionan
            $updatableFields = [
                'error_message',
                'error_code',
                'swish_id',
                'message',
                'response_data',
                'location_url'
            ];

            foreach ($updatableFields as $field) {
                if ($request->has($field)) {
                    $payout->$field = $request->$field;
                }
            }

            $payout->save();

            // Recargar relaciones y agregar información adicional
            $payout->load('user', 'state');
            
            $payoutData = $payout->toArray();
            $payoutData['created_at_formatted'] = $payout->created_at?->format('Y-m-d H:i:s');
            $payoutData['updated_at_formatted'] = $payout->updated_at?->format('Y-m-d H:i:s');
            $payoutData['has_error'] = !empty($payout->error_message);
            $payoutData['is_completed'] = in_array($payout->status, ['PAID', 'CANCELLED']);
            $payoutData['is_pending'] = in_array($payout->status, ['CREATED', 'DEBITED']);

            return response()->json([
                'success' => true,
                'message' => 'Betalningen uppdaterades framgångsrikt',
                'data' => [ 
                    'payout' => $payoutData
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
