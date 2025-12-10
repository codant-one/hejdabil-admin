<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use App\Models\PayoutState;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SwishPayoutController extends Controller
{
  
    /**
     * Callback that Swish calls to update the status of a payout.
     * 
     * Para probar en local:
     * curl -X POST http://localhost:8000/api/swish/payout/callback \
     *   -H "Content-Type: application/json" \
     *   -d '{"id":"SWISH-ID-123","status":"PAID"}'
     */
    public function handle(Request $request)
    {
        // Log completo del callback recibido
        Log::info('=== SWISH CALLBACK RECEIVED ===');
        Log::info('Headers: ' . json_encode($request->headers->all()));
        Log::info('Payload: ' . json_encode($request->all()));
        Log::info('Raw Body: ' . $request->getContent());
        
        $payload = $request->all();

        // Identificar payout - Swish puede enviar diferentes estructuras
        $payoutId = $payload['id'] ?? $payload['payoutInstructionUUID'] ?? null;
        $status   = $payload['status'] ?? null;

        if (!$payoutId || !$status) {
            Log::warning('Swish callback: Invalid payload', [
                'payoutId' => $payoutId,
                'status' => $status,
                'full_payload' => $payload
            ]);
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        // Buscar por swish_id o payout_instruction_uuid
        $payout = Payout::where('swish_id', $payoutId)
                        ->orWhere('payout_instruction_uuid', $payoutId)
                        ->first();
        
        if (!$payout) {
            Log::warning('Swish callback: Payout not found', [
                'payoutId' => $payoutId
            ]);
            return response()->json(['error' => 'Payout not found'], 404);
        }

        Log::info('Swish callback: Payout found', [
            'payout_id' => $payout->id,
            'current_status' => $payout->payout_state_id,
            'new_status' => $status
        ]);

        // Mapear a payout_states
        $stateId = PayoutState::where('label', $status)->value('id');
        if ($stateId) {
            $payout->payout_state_id = $stateId;
            Log::info('Swish callback: State mapped', ['state_id' => $stateId]);
        } else {
            Log::warning('Swish callback: State not found in payout_states', ['status' => $status]);
        }

        $payout->save();

        Log::info('Swish callback: Payout updated successfully', [
            'payout_id' => $payout->id,
            'payout_state_id' => $payout->payout_state_id,
            'status_label' => $status
        ]);

        return response()->json(['ok' => true]);
    }
}