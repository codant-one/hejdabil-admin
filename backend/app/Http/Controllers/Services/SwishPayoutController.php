<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use App\Services\SwishPayout;
use App\Models\Payout;
use App\Models\PayoutState;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SwishPayoutController extends Controller
{
    /**
     * Endpoint que crea un payout en Swish y lo registra en la tabla payouts.
     */
    public function store(Request $request, SwishPayout $swish)
    {
        try {
            $data = $request->validate([
                // payer_alias aquí representa el alias del destinatario (payee)
                'payer_alias' => 'required|string',
                'amount'      => 'required|numeric|min:1',
                'payee_ssn'   => 'nullable|digits:12', // YYYYMMDDXXXX
            ]);

            // Referencia corta y alfanumérica (muchos tenants limitan formato/longitud)
            $ref = 'REF' . strtoupper(Str::random(9));

            // Normalizar MSISDN sueco a formato E.164 sin '+' (46...) para reducir errores ACMT17
            $rawAlias = preg_replace('/\s+/', '', $data['payer_alias']);
            $rawAlias = ltrim($rawAlias, '+');
            if (preg_match('/^0\d{9}$/', $rawAlias)) {
                // 0XXXXXXXXX -> 46XXXXXXXXX
                $rawAlias = '46' . substr($rawAlias, 1);
            }
            $payeeAlias = $rawAlias;

            // Llamada al servicio de Swish
            $response = $swish->createPayout(
                $payeeAlias,
                $data['amount'],
                $ref,
                $data['payee_ssn'] ?? null
            );

            // Log de la respuesta completa para depuración
            Log::info('Swish Payout response', [
                'status'  => $response->status(),
                'headers' => $response->headers(),
                'body'    => $response->json(),
            ]);

            // Si Swish devuelve error (4xx/5xx), NO guardamos nada en la BD
            if (!$response->successful()) {
                $body = $response->json();

                // Extraemos código y mensaje si vienen en el formato de Swish
                $errorCode = null;
                $errorMessage = null;
                if (is_array($body)) {
                    // En sandbox suele venir como array de errores
                    $first = $body[0] ?? null;
                    if (is_array($first)) {
                        $errorCode = $first['errorCode'] ?? null;
                        $errorMessage = $first['errorMessage'] ?? null;
                    }
                }

                // Evitar desloguear al frontend: mapear 401 externos a 422
                $status = $response->status();
                if ($status === 401) {
                    $status = 422; // Unprocessable Entity para errores de parámetros
                }

                return response()->json([
                    'success' => false,
                    'message' => $errorMessage ?? 'Swish payout error',
                    'errorCode' => $errorCode,
                    'errors'  => $body,
                ], $status);
            }

            // El ID de payout suele venir en el header Location: .../v1/payouts/{id}
            $locationHeader = $response->header('Location') ?? $response->header('location');
            $payoutId = null;

            if ($locationHeader) {
                $path = parse_url($locationHeader, PHP_URL_PATH);
                $payoutId = $path ? basename($path) : null;
            } else {
                // Fallback: intentar obtenerlo del cuerpo por si Swish lo envía ahí
                $responseJson = $response->json();
                if (is_array($responseJson)) {
                    $payoutId = $responseJson['id'] ?? $responseJson['payoutId'] ?? $responseJson['paymentReference'] ?? null;
                }
            }

            // Estado inicial: buscamos un estado "PENDING" si existe, si no usamos el id por defecto (1)
            $pendingStateId = PayoutState::where('name', 'PENDING')->value('id') ?? 1;

            $payout = Payout::createPayout($request, [
                'payer_alias'      => $data['payer_alias'],
                'amount'           => $data['amount'],
                'reference'        => $ref,
                'user_id'          => $request->user()->id,
                'payout_state_id'  => $pendingStateId,
                'swish_id'         => $payoutId,
            ]);

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
     * Callback que Swish llama para actualizar el estado de un payout.
     */
    public function handle(Request $request)
    {
        $payload = $request->all();

        // Identificar payout
        $payoutId = $payload['id'] ?? null;
        $status   = $payload['status'] ?? null;

        if (!$payoutId || !$status) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        $payout = Payout::where('swish_id', $payoutId)->first();
        if (!$payout) {
            return response()->json(['error' => 'Not found'], 404);
        }

        // Intentamos mapear el status devuelto por Swish a un registro de payout_states.name = $status
        $stateId = PayoutState::where('name', $status)->value('id');
        if ($stateId) {
            $payout->payout_state_id = $stateId;
        }

        $payout->save();

        return response()->json(['ok' => true]);
    }
}