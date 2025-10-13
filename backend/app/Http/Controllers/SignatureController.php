<?php

namespace App\Http\Controllers;

use App\Models\Agreement;
use App\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\SignatureRequestMail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use PDF;
use Illuminate\Support\Facades\Response;
use App\Mail\SignedDocumentMail; 
use Illuminate\Support\Facades\Log;

use App\Models\UserDetails;
use App\Models\User;
use App\Models\Config;

class SignatureController extends Controller
{
    /**
     * Método 1: Iniciar el proceso de firma (Activado desde tu Dashboard de Vue).
     * URL: POST /api/agreements/{agreement}/send-signature-request
     */
    public function sendSignatureRequest(Agreement $agreement, Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'x' => 'required|numeric',
            'y' => 'required|numeric',
            'page' => 'required|integer',
        ]);
        // 1. Verificar si el contrato ya tiene un PDF generado.
        if (!$agreement->file) {
            return response()->json(['message' => 'Este contrato aún no tiene un PDF generado para firmar.'], 422);
        }

        // 2. Obtener el cliente asociado al contrato. Tu modelo usa 'agreement_client'.
        $agreementClient = $agreement->agreement_client;
        if (!$agreementClient || !$agreementClient->email) {
            return response()->json(['message' => 'El contrato no tiene un cliente con email asociado.'], 422);
        }

        // 3. Generar un token único para la solicitud de firma.
        $signingToken = Str::uuid()->toString();
        
        // 4. Crear el registro en la tabla 'tokens'.
        $token = $agreement->tokens()->create([
            'signing_token' => $signingToken,
            'recipient_email'     => $validated['email'],
            'token_expires_at'    => now()->addDays(7),
            'signature_status'        => 'sent',
            'placement_x'   => $validated['x'],
            'placement_y'   => $validated['y'],
            'placement_page'=> $validated['page'],
        ]);

        // 5. Enviar el email al cliente con el enlace de firma.
        Mail::to($validated['email'])->send(new SignatureRequestMail($token));
        
        return response()->json(['message' => 'Solicitud de firma enviada con éxito.']);
    }

    //Firmar con posición fija.
    public function sendStaticSignatureRequest(Agreement $agreement, Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);
        // 1. Verificar si el contrato ya tiene un PDF generado.
        if (!$agreement->file) {
            return response()->json(['message' => 'Este contrato aún no tiene un PDF generado para firmar.'], 422);
        }

        // 2. Obtener el cliente asociado al contrato.
        $agreementClient = $agreement->agreement_client;
        if (!$agreementClient || !$agreementClient->email) {
            return response()->json(['message' => 'El contrato no tiene un cliente con email asociado.'], 422);
        }

        // 3. Generar un token único.
        $signingToken = Str::uuid()->toString();
        
        // 4. Crear el registro en 'tokens' SIN coordenadas.
        $token = $agreement->tokens()->create([
            'signing_token' => $signingToken,
            'recipient_email'     => $validated['email'],
            'token_expires_at'    => now()->addDays(7),
            'signature_status'        => 'sent',
            'placement_x'   => null, // Guardamos null para identificar que es una firma estática
            'placement_y'   => null,
            'placement_page'=> 1,    // Asumimos página 1 por defecto
        ]);

        // 5. Enviar el email al cliente.
        Mail::to($validated['email'])->send(new SignatureRequestMail($token));
        
        return response()->json(['message' => 'Solicitud de firma enviada con éxito.']);
    }

    /**
     * Método 2: Mostrar la página de firma (Activado cuando el cliente abre el enlace).
     * URL: GET /sign/{token}
     */
    public function showSigningPage($tokenString)
    {
        // Validar que el token es correcto, está pendiente y no ha expirado.
        $token = Token::where('signing_token', $tokenString)
                  ->where('token_expires_at', '>', now())
                  ->first();

        if (!$token) {
            abort(404, 'Enlace de firma no válido o expirado');
        }

        // Verificar si el token ya fue usado para firmar
        if ($token->signature_status === 'signed') {
            return view('welcome');
        }

        // Si el token está en estado 'sent', procedemos normalmente
        if ($token->signature_status === 'sent') {
            return view('welcome');
        }
        
        // Para cualquier otro estado no contemplado
        abort(404, 'Estado de firma no válido.');
    }

    /**
     * Obtener el PDF firmado si el token corresponde a un documento ya firmado
     * URL: GET /api/signatures/{token}/get-signed-pdf
     */
    public function getSignedPdf($tokenString)
    {
        $token = Token::where('signing_token', $tokenString)
                    ->where('signature_status', 'signed')
                    ->firstOrFail();
        
        $agreement = $token->agreement;
        
        // Usar el PDF firmado guardado en el token o en el agreement
        $pdfPath = $token->signed_pdf_path ?? $agreement->file;
        
        if ($pdfPath && Storage::disk('public')->exists($pdfPath)) {
            $path = storage_path('app/public/' . $pdfPath);
            return response()->file($path);
        }
        
        abort(404, 'Archivo PDF firmado no encontrado.');
    }

    /**
     * Obtener el estado del token y detalles adicionales
     * URL: GET /api/signatures/{token}/status
     */
    public function getTokenStatus($tokenString)
    {
        $token = Token::where('signing_token', $tokenString)->first();
        
        if (!$token) {
            return response()->json([
                'status' => 'invalid',
                'message' => 'Token no válido'
            ], 404);
        }
        
        if ($token->token_expires_at < now() && $token->signature_status !== 'signed') {
            return response()->json([
                'status' => 'expired',
                'message' => 'El enlace de firma ha expirado'
            ], 410);
        }
        
        $response = [
            'status' => $token->signature_status,
            'signed_at' => $token->signed_at,
            'agreement_id' => $token->agreement->agreement_id
        ];
        
        if ($token->signature_status === 'signed') {
            $response['message'] = 'Este documento ya fue firmado';
            $response['signed_date_formatted'] = $token->signed_at ? 
                \Carbon\Carbon::parse($token->signed_at)->format('d/m/Y H:i') : null;
        }
        
        return response()->json($response);
    }

    /**
     * Método 3: Guardar la firma y Regenerar el PDF (Activado desde la página de firma de Vue).
     * URL: POST /api/signatures/submit/{token}
     */
    public function storeSignature(Request $request, $tokenString)
    {
        // 1. Validar de nuevo el token para máxima seguridad.
        $token = Token::where('signing_token', $tokenString)
                      ->where('signature_status', 'sent')
                      ->where('token_expires_at', '>', now())
                      ->firstOrFail();

        // 2. Validar que la firma viene en la petición.
        $request->validate(['signature' => 'required|string']);

        $validated = $request->validate([
            'signature' => 'required|string'
        ]);

        // 3. Decodificar y guardar la imagen de la firma.
        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->signature));
        $agreement = $token->agreement;
        $imageName = 'signature_' . $agreement->id . '_' . time() . '.png';
        $signaturePath = 'signatures/' . $imageName;
        Storage::disk('public')->put($signaturePath, $imageData);

        // --- LÓGICA DE REGENERACIÓN DEL PDF ADAPTADA A TU MODELO ---
        // 4. Regenerar el PDF, esta vez pasando la URL de la firma.
        $signedPdfPath = $this->regeneratePdfWithSignature(
            $agreement,
            $signaturePath,
            $token->placement_x,
            $token->placement_y
        );
        if (!$signedPdfPath) {
            return response()->json(['message' => 'No se pudo regenerar el PDF con la firma.'], 500);
        }

        // 5. Actualizar el registro del token con el estado 'signed' y las rutas de los archivos.
        $token->update([
            'signature_status'                 => 'signed',
            'signed_at'              => now(),
            'signature_image_path'   => $signaturePath,
            'signed_pdf_path'        => $signedPdfPath, // Guardamos la ruta del nuevo PDF firmado
        ]);

        // 6. Opcional pero recomendado: Actualizar el campo 'file' del Agreement con la nueva ruta.
        $agreement->file = $signedPdfPath;
        $agreement->save();

        try {
            $recipientEmail = $token->recipient_email ?? $token->agreement->agreement_client->email;
            if ($recipientEmail) {
                $pdfFullPath = storage_path('app/public/' . $signedPdfPath);
                Mail::to($recipientEmail)->send(new SignedDocumentMail($agreement, $pdfFullPath));
            }
        } catch (\Exception $e) {
            Log::error('Kunde inte skicka signerat PDF via e-post för avtal #' . $agreement->id . ': ' . $e->getMessage());
        }
        
        // 7. Devolver una respuesta exitosa a Vue.
        return response()->json([
            'message'      => 'Contrato firmado con éxito.',
            'download_url' => Storage::disk('public')->url($signedPdfPath)
        ]);
    }
    
    /**
     * Helper privado para regenerar el PDF con la firma.
     * Esta función adapta la lógica de tu método Agreement::generatePdf().
     */
    private function regeneratePdfWithSignature(Agreement $agreement, string $signatureUrl, ?float $x, ?float $y)
    {
        // 1. Cargar todas las relaciones necesarias.
        $agreement->load([
            'agreement_type', 'guaranty_type', 'insurance_type', 'currency', 'iva',
            'offer', 'commission.vehicle', 'commission.vehicle.model.brand', 'commission.vehicle.fuel', 
            'commission.vehicle.gearbox', 'commission.client.client_type',
            'payment_types', 'vehicle_interchange.model.brand',
            'vehicle_interchange.carbody', 'vehicle_interchange.iva_purchase',
            'agreement_client', 'vehicle_client.vehicle.model.brand',
            'vehicle_client.vehicle.fuel', 'vehicle_client.vehicle.gearbox',
            'vehicle_client.vehicle.payment.payment_types', 
            'supplier.user.userDetail'
        ]);

        // 2. Determinar quién es el usuario creador.
        $creatorUser = null;
        if ($agreement->supplier && $agreement->supplier->user) {
            $creatorUser = $agreement->supplier->user;
        }
        // Si no hay proveedor, podrías añadir lógica para buscar un usuario admin aquí si fuera necesario.
        $fullPath = Storage::disk('public')->path($signatureUrl);
        $imageData = base64_encode(file_get_contents($fullPath));
        $signatureImageSrc = 'data:image/png;base64,' . $imageData;

        // 3. Preparar los datos para la vista, incluyendo el usuario y la firma.
        if ($agreement->supplier && is_null($agreement->supplier->boss_id)) {//supplier
            $user = UserDetails::with(['user'])->find($agreement->supplier->user_id);
            $company = $user->user->userDetail;
            $company->email = $user->user->email;
            $company->name = $user->user->name;
            $company->last_name = $user->user->last_name;
        } else if ($agreement->supplier && !is_null($agreement->supplier->boss_id)) {//user
            $user = User::with(['userDetail', 'supplier.boss.user.userDetail'])->find($agreement->supplier->user_id);
            $company = $user->supplier->boss->user->userDetail;
            $company->email = $user->supplier->boss->user->email;
            $company->name = $user->supplier->boss->user->name;
            $company->last_name = $user->supplier->boss->user->last_name;
        } else { //Admin
            $configCompany = Config::getByKey('company') ?? ['value' => '[]'];
            $configLogo    = Config::getByKey('logo')    ?? ['value' => '[]'];
            
            // Extraer el "value" soportando array u object
            $getValue = function ($cfg) {
                if (is_array($cfg)) 
                    return $cfg['value'] ?? '[]';
                if (is_object($cfg) && isset($cfg->value))
                    return $cfg->value;
                return '[]';
            };
            
            $companyRaw = $getValue($configCompany);
            $logoRaw    = $getValue($configLogo);
            
            $decodeSafe = function ($raw) {
                $decoded = json_decode($raw);

                if (is_string($decoded))
                    $decoded = json_decode($decoded);
            
                if (!is_object($decoded)) 
                    $decoded = (object) [];
            
                return $decoded;
            };
            
            $company = $decodeSafe($companyRaw);
            $logoObj    = $decodeSafe($logoRaw);
            
            $company->logo = $logoObj->logo ?? null;
        }

        $data = [
            'agreement'     => $agreement,
            'company'       => $company,
            'signature_url' => $signatureImageSrc,
            'signature_x'   => $x,
            'signature_y'   => $y,
        ];

        // 4. Determinar la vista y el nombre del archivo (esta parte ya estaba bien).
        $viewName = '';
        $fileName = '';
        switch ($agreement->agreement_type_id) {
            case 1:
                $viewName = 'pdfs.sales';
                $fileName = 'försäljningsavtal-'.$agreement->vehicle_client->vehicle->reg_num.'-'.$agreement->agreement_id.'-signed.pdf';
                break;
            case 2:
                $viewName = 'pdfs.purchase';
                $fileName = 'inköpsavtal-'.$agreement->vehicle_client->vehicle->reg_num.'-'.$agreement->agreement_id.'-signed.pdf';
                break;
            case 3:
                $viewName = 'pdfs.mediation';
                $fileName = 'förmedlingsavtal-'.$agreement->commission->vehicle->reg_num.'-'.$agreement->agreement_id.'-signed.pdf';
                break;
            case 4:
                $viewName = 'pdfs.business';
                $fileName = 'prisförslag-'.$agreement->offer->reg_num.'-'.$agreement->offer->offer_id.'-signed.pdf';
                break;
            default:
                return null;
        }

        // 5. Generar y guardar el nuevo PDF firmado.
        $filePath = 'pdfs/' . $fileName;
        PDF::loadView($viewName, $data)->save(storage_path('app/public/' . $filePath));
        
        return $filePath;
    }

    public function getUnsignedPdf($tokenString)
    {
        $token = Token::where('signing_token', $tokenString)->firstOrFail();
        $agreement = $token->agreement;
    
        if ($agreement && $agreement->file && Storage::disk('public')->exists($agreement->file)) {
            $path = storage_path('app/public/' . $agreement->file);
            return response()->file($path);
        }
        abort(404, 'Archivo PDF no encontrado.');
    }

    public function getAdminPreviewPdf(Agreement $agreement)
    {
        
        if ($agreement && $agreement->file && Storage::disk('public')->exists($agreement->file)) {
            $path = storage_path('app/public/' . $agreement->file);
            return response()->file($path);
        }

        abort(404, 'Archivo PDF no encontrado para este contrato.');
    }

    public function getSignatureDetails($tokenString)
    {
        $token = Token::where('signing_token', $tokenString)->firstOrFail();

        // Lógica para determinar la alineación de la firma
        $alignment = 'left'; // Por defecto, la firma va a la izquierda
        $agreementTypeId = $token->agreement->agreement_type_id;

        // Para el contrato de Compra (Purchase), el cliente es el vendedor,
        // por lo que su firma va a la DERECHA.
        if ($agreementTypeId == 2) { // 2 = Purchase
            $alignment = 'right';
        }
        // Para los demás (Sales, Mediation), la firma del cliente va a la izquierda.
        // No necesitamos más condiciones, el valor por defecto 'left' ya funciona para ellos.

        return response()->json([
            'placement_x' => $token->placement_x,
            'placement_y' => $token->placement_y,
            'placement_page' => $token->placement_page,
            'signature_alignment' => $alignment,
        ]);
    }
}