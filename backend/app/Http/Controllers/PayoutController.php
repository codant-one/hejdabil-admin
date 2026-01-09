<?php

namespace App\Http\Controllers;

use App\Http\Requests\SwishRequest;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use App\Services\SwishPayout;

use Spatie\Permission\Middlewares\PermissionMiddleware;

use App\Models\Payout;
use App\Models\PayoutState;
use App\Models\Config;
use App\Models\Supplier;

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
                                    'user_id',
                                    'state_id'
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
     public function store(SwishRequest $request, SwishPayout $swish)
    {
        try {
            // Valide master_password
            $user = Auth::user();
            $role = $user->roles->first()->name ?? null;
            $masterPasswordValid = false;
            
            if ($role === 'Supplier') {
                $supplier = $user->supplier;
                if (!$supplier || !$supplier->master_password) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Inget säkerhetslösenord konfigurerat för leverantören',
                    ], 422);
                }
                $masterPasswordValid = ($request->master_password === $supplier->master_password);
            } else { // por el momento no tenemos swish para otros roles
                $config = Config::getByKey('setting');
                
                if (!$config) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Inget säkerhetslösenord konfigurerat',
                    ], 422);
                }
                
                // Manejar si $config es array u objeto
                $configValue = is_array($config) ? ($config['value'] ?? null) : ($config->value ?? null);
                
                if (!$configValue) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Inget säkerhetslösenord konfigurerat',
                    ], 422);
                }
                
                $configArr = json_decode($configValue, true);
                
                if (!isset($configArr['master_password'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Inget säkerhetslösenord konfigurerat',
                    ], 422);
                }
                
                $master_password = $configArr['master_password'];
                $masterPasswordValid = ($request->master_password === $master_password);
            }

            if (!$masterPasswordValid) {// no coincide el master password
                return response()->json([
                    'success' => false,
                    'message' => 'Felaktigt säkerhetslösenord',
                ], 422);
            }

            // Reference for the payout
            $ref = 'REF' . strtoupper(Str::random(9));

            // Normalize Swedish MSISDN to E.164 format without ‘+’ (46...) to reduce errors ACMT17
            $rawAlias = preg_replace('/\s+/', '', $request->payee_alias);
            $rawAlias = ltrim($rawAlias, '+');
            if (preg_match('/^0\d{9}$/', $rawAlias))
                $rawAlias = '46' . substr($rawAlias, 1);
            $payeeAlias = $rawAlias;

            $payoutInstructionUUID = strtoupper(str_replace('-', '', Str::uuid()->toString()));

            $request->merge([
                'payer_alias' => str_replace('-', '', $request->payer_alias),
                'payout_instruction_uuid' => $payoutInstructionUUID,
                'reference' => $ref,
                'amount' => number_format($request->amount, 2, '.', ''),
                'currency' => 'SEK',
                'payout_type' => 'PAYOUT',
                'message' => $request->message ?? 'Payout from ' . config('app.name'),
                'instruction_date' => Carbon::now('UTC')->format('Y-m-d\TH:i:s\Z'),
                'signing_certificate_serial_number' => $swish->getSigningCertificateSerialNumber(), // Signature certificate serial number
            ]);

            // Service swish call
            $response = $swish->createPayout(
                $request
            );

            // Si Swish devuelve error (4xx/5xx), guardamos el error en la BD
            if (!$response->successful()) {
                $body = $response->json();
                $errorCode = null;
                $errorMessage = null;

                if (is_array($body)) {
                    $first = $body[0] ?? null;
                    if (is_array($first)) {
                        $errorCode = $first['errorCode'] ?? null;
                        $errorMessage = $first['errorMessage'] ?? null;
                    }
                }

                // Buscar estado de error
                $errorStateId = PayoutState::where('name', 'Misslyckad')->orWhere('label', 'failed')->value('id');

                $request->merge([
                    'payout_state_id' => $errorStateId ?? 1,
                    'error_message'   => $errorMessage,
                    'error_code'      => $errorCode,
                ]);

                Payout::createPayout($request);

                // Avoid logging out on the frontend: map external 401s to 422s
                $status = $response->status();
                if ($status === 401) {
                    $status = 422; // Unprocessable Entity for parameter errors
                }

                return response()->json([
                    'success' => false,
                    'message' => $errorMessage ?? 'Swish payout error',
                    'errorCode' => $errorCode,
                    'errors'  => $body,
                ], $status);
            }

            // The payout ID usually comes in the Location header: .../v1/payouts/{id}
            $locationHeader = $response->header('Location') ?? $response->header('location');
            $payoutId = null;

            if ($locationHeader) {
                $path = parse_url($locationHeader, PHP_URL_PATH);
                $payoutId = $path ? basename($path) : null;
            } else {
                // Fallback: try to get it from the body in case Swish sends it there
                $responseJson = $response->json();
                if (is_array($responseJson)) {
                    $payoutId = $responseJson['id'] ?? $responseJson['payoutId'] ?? $responseJson['paymentReference'] ?? null;
                }
            }

            // Obtener el status de la respuesta JSON
            $responseStatus = $response->json()['status'] ?? 'CREATED';
            $stateId = PayoutState::where('name', $responseStatus)->orWhere('label', strtolower($responseStatus))->value('id');

            // Si no se encuentra, usar pending o default 1
            if (!$stateId) {
                $stateId = PayoutState::where('label', 'pending')->value('id') ?? 1;
            }

            $request->merge([
                'payout_state_id' => $stateId,
                'swish_id'        => $payoutId,
                'location_url'    => $locationHeader,
            ]);

            $payout = Payout::createPayout($request);
 
            return response()->json([
                'success' => true,
                'data'    => [
                    'payout'   => $payout,
                    'swishRaw' => $response->json(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Swish Payout exception', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success'  => false,
                'message'  => 'internal_error',
                'exception'=> $e->getMessage(),
            ], 500);
        }
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

     public function info() {

        try {

            $suppliers = 
                Supplier::with(['user' => fn($q) => $q->withTrashed()])
                    ->whereNull('boss_id')
                    ->withTrashed()
                    ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'suppliers' => $suppliers
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
}
