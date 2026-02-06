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
        
            $query = Payout::with(['state', 'user' => function($query) {
                                $query->whereNull('deleted_at');
                            }])
                           ->whereHas('user', function($query) {
                                $query->whereNull('deleted_at');
                            })
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
            } elseif ($role === 'User') {
                $supplier = $user->supplier->boss;
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

            // Reference for the payout
            $ref = 'REF' . strtoupper(Str::random(9));

            // Si el master password no es válido, crear el registro sin llamar a la API
            if (!$masterPasswordValid) {
                // Normalize Swedish MSISDN to E.164 format without '+' (46...)
                $rawAlias = preg_replace('/\s+/', '', $request->payee_alias);
                $rawAlias = ltrim($rawAlias, '+');
                if (preg_match('/^0\d{9}$/', $rawAlias))
                    $rawAlias = '46' . substr($rawAlias, 1);

                $payoutInstructionUUID = strtoupper(str_replace('-', '', Str::uuid()->toString()));

                // Buscar estado pendiente
                $pendingStateId = PayoutState::where('label', 'created')->orWhere('name', 'Väntande')->value('id') ?? 1;

                $request->merge([
                    'payer_alias' => str_replace('-', '', $request->payer_alias),
                    'payee_alias' => $rawAlias,
                    'payout_instruction_uuid' => $payoutInstructionUUID,
                    'reference' => $ref,
                    'amount' => number_format($request->amount, 2, '.', ''),
                    'currency' => 'SEK',
                    'payout_type' => 'PAYOUT',
                    'message' => $request->message ?? 'Payout from ' . config('app.name'),
                    'instruction_date' => Carbon::now('UTC')->format('Y-m-d\TH:i:s\Z'),
                    'payout_state_id' => $pendingStateId,
                ]);

                $payout = Payout::createPayout($request);

                return response()->json([
                    'success' => false,
                    'message' => 'Felaktigt säkerhetslösenord',
                ], 422);
            }

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
                    'message' => $errorMessage ?? 'Fel vid Swish-utbetalning',
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
                    'payout'   => Payout::with(['state', 'user'])->find($payout->id),
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
    public function update(SwishRequest $request, $id, SwishPayout $swish): JsonResponse
    {
        try {
            $payout = Payout::find($id);
            
            if (!$payout) {
                return response()->json([
                    'success' => false,
                    'message' => 'Betalningen hittades inte'
                ], 404);
            }

            // Validate master_password
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
            } elseif ($role === 'User') {
                $supplier = $user->supplier->boss;
                if (!$supplier || !$supplier->master_password) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Inget säkerhetslösenord konfigurerat för leverantören',
                    ], 422);
                }
                $masterPasswordValid = ($request->master_password === $supplier->master_password);
            } else {
                $config = Config::getByKey('setting');
                
                if (!$config) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Inget säkerhetslösenord konfigurerat',
                    ], 422);
                }
                
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

            if (!$masterPasswordValid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Felaktigt säkerhetslösenord',
                ], 422);
            }

            // Use existing reference from database
            $ref = $payout->reference;

            // Normalize Swedish MSISDN to E.164 format
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
                'signing_certificate_serial_number' => $swish->getSigningCertificateSerialNumber(),
            ]);

            // Service swish call
            $response = $swish->createPayout($request);

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

                $errorStateId = PayoutState::where('name', 'Misslyckad')->orWhere('label', 'failed')->value('id');

                $payout->update([
                    'payout_state_id' => $errorStateId ?? 1,
                    'error_message'   => $errorMessage,
                    'error_code'      => $errorCode,
                ]);

                $status = $response->status();
                if ($status === 401) {
                    $status = 422;
                }

                return response()->json([
                    'success' => false,
                    'message' => $errorMessage ?? 'Fel vid Swish-utbetalning',
                    'errorCode' => $errorCode,
                    'errors'  => $body,
                ], $status);
            }

            $locationHeader = $response->header('Location') ?? $response->header('location');
            $payoutId = null;

            if ($locationHeader) {
                $path = parse_url($locationHeader, PHP_URL_PATH);
                $payoutId = $path ? basename($path) : null;
            } else {
                $responseJson = $response->json();
                if (is_array($responseJson)) {
                    $payoutId = $responseJson['id'] ?? $responseJson['payoutId'] ?? $responseJson['paymentReference'] ?? null;
                }
            }

            $responseStatus = $response->json()['status'] ?? 'CREATED';
            $stateId = PayoutState::where('name', $responseStatus)->orWhere('label', strtolower($responseStatus))->value('id');

            if (!$stateId) {
                $stateId = PayoutState::where('label', 'pending')->value('id') ?? 1;
            }

            $payout->update([
                'payout_state_id' => $stateId,
                'swish_id'        => $payoutId,
                'location_url'    => $locationHeader,
                'payee_alias'     => $payeeAlias,
                'payee_ssn'       => $request->payee_ssn,
                'amount'          => $request->amount,
                'message'         => $request->message,
            ]);
 
            return response()->json([
                'success' => true,
                'data'    => [
                    'payout'   => Payout::with(['state', 'user'])->find($payout->id),
                    'swishRaw' => $response->json(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Swish Payout update exception', [
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
                    'payout' => Payout::with(['state', 'user'])->find($payout->id)
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

    public function cancel($id)
    {
        try {

            $payout = Payout::find($id);
        
            if (!$payout)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Betalningen hittades inte'
                ], 404);
            
            $payout->cancelPayout($id);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'payout' => Payout::with(['state', 'user'])->find($payout->id)
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
     * Save receipt image from frontend
     */
    public function saveReceiptImage(Request $request, $id)
    {
        try {
            $payout = Payout::find($id);
        
            if (!$payout) {
                return response()->json([
                    'success' => false,
                    'message' => 'Betalningen hittades inte'
                ], 404);
            }

            // Validate that the image is coming
            $request->validate([
                'image' => 'required|file|mimes:png,jpg,jpeg|max:5120' // max 5MB
            ]);

            // Save the image
            $image = $request->file('image');
            $fileName = 'payouts/' . $payout->reference . '_' . time() . '.png';
            
            // Save in storage/app/public/payouts
            $path = $image->storeAs('public/' . dirname($fileName), basename($fileName));
            
            // Update the payout with the image path
            $payout->image = $fileName;
            $payout->save();

            return response()->json([
                'success' => true,
                'data' => [
                    'payout' => Payout::with(['state', 'user'])->find($payout->id),
                    'image_url' => $payout->image_url
                ]
            ]);

        } catch(\Exception $ex) {
            Log::error('Error saving receipt image', [
                'payout_id' => $id,
                'message' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fel vid sparande av bilden',
                'exception' => $ex->getMessage()
            ], 500);
        }
    }

      /**
     * Send document(s) via email
     */
    public function send(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'ids' => 'required',
                'email' => 'required|email',
            ]);

            $result = Payout::sendPayout($request);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Utbetalningen har skickats via e-post'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Det gick inte att skicka betalningsbeviset.'
            ], 400);

        } catch (\Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Ett fel inträffade: ' . $ex->getMessage()
            ], 500);
        }
    }
}
