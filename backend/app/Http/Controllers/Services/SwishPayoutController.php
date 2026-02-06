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
        $payload = $request->all();

        // Identificar payout - Swish puede enviar diferentes estructuras
        $payoutId = $payload['id'] ?? $payload['payoutInstructionUUID'] ?? null;
        $status   = $payload['status'] ?? null;
        $reference = $payload['payerPaymentReference'] ?? $payoutId ?? 'unknown';

        // Log completo del callback recibido
        $this->generateLog($reference, 'CALLBACK RECEIVED', [
            'headers' => $request->headers->all(),
            'payload' => $payload,
            'raw_body' => $request->getContent(),
        ]);

        if (!$payoutId || !$status) {
            $this->generateLog($reference, 'Invalid payload', [
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
            $this->generateLog($reference, 'Payout not found', [
                'payoutId' => $payoutId
            ]);
            return response()->json(['error' => 'Payout not found'], 404);
        }

        $this->generateLog($reference, 'Payout found', [
            'payout_id' => $payout->id,
            'current_status' => $payout->payout_state_id,
            'new_status' => $status
        ]);

        // Mapear a payout_states
        $stateId = PayoutState::where('label', $status)->value('id');
        if ($stateId) {
            $payout->payout_state_id = $stateId;
            $this->generateLog($reference, 'State mapped', ['state_id' => $stateId]);
        } else {
            $this->generateLog($reference, 'State not found in payout_states', ['status' => $status]);
        }

        $payout->save();

        $this->generateLog($reference, 'Payout updated successfully', [
            'payout_id' => $payout->id,
            'payout_state_id' => $payout->payout_state_id,
            'status_label' => $status
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * Generates a log file for the payout callback.
     */
    private function generateLog(string $reference, string $action, array $data): void
    {
        if (!file_exists(storage_path('logs/swish-payouts'))) {
            mkdir(storage_path('logs/swish-payouts'), 0755, true);
        }

        $logPath = storage_path("logs/swish-payouts/{$reference}.log");

        $log = Log::build([
            'driver' => 'single',
            'path' => $logPath,
            'level' => 'debug',
        ]);

        $log->info('Date: ' . now());
        $log->info('Action: ' . $action);
        $log->info('Data: ' . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}