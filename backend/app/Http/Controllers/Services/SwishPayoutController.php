<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use App\Models\PayoutState;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

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

        // Generar imagen del recibo si el pago fue exitoso (PAID)
        if (strtoupper($status) === 'PAID') {
            $this->generateReceiptImage($payout);
        }

        $this->generateLog($reference, 'Payout updated successfully', [
            'payout_id' => $payout->id,
            'payout_state_id' => $payout->payout_state_id,
            'status_label' => $status
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * Generates a receipt image (PNG) for the payout using DomPDF.
     */
    private function generateReceiptImage(Payout $payout): void
    {
        try {
            $pdf = Pdf::loadView('pdf.payout-receipt', [
                'payout' => $payout,
                'imageBase64' => null
            ]);

            // Guardar como imagen PNG
            $fileName = 'payouts/' . $payout->reference . '_' . time() . '.png';
            $storagePath = storage_path('app/public/' . $fileName);
            
            // Asegurar que el directorio existe
            if (!file_exists(dirname($storagePath))) {
                mkdir(dirname($storagePath), 0755, true);
            }

            // Obtener el PDF como string y convertir a imagen
            $pdfContent = $pdf->output();
            
            // Usar Imagick si está disponible
            if (extension_loaded('imagick')) {
                $imagick = new \Imagick();
                $imagick->setResolution(150, 150);
                $imagick->readImageBlob($pdfContent);
                $imagick->setImageFormat('png');
                $imagick->writeImage($storagePath);
                $imagick->destroy();
            } else {
                // Fallback: guardar el PDF directamente
                $fileName = 'payouts/' . $payout->reference . '_' . time() . '.pdf';
                $storagePath = storage_path('app/public/' . $fileName);
                file_put_contents($storagePath, $pdfContent);
            }

            // Actualizar el payout con la ruta de la imagen
            $payout->image = $fileName;
            $payout->save();

            $this->generateLog($payout->reference, 'Receipt image generated', [
                'payout_id' => $payout->id,
                'image_path' => $fileName
            ]);

        } catch (\Exception $e) {
            $this->generateLog($payout->reference, 'Error generating receipt image', [
                'payout_id' => $payout->id,
                'error' => $e->getMessage()
            ]);
        }
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