<?php

namespace App\Http\Controllers;

use App\Models\Agreement;
use App\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\SignatureRequestMail; // Lo crearemos en el siguiente paso
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth; // Necesario para regenerar el PDF
use PDF; // Tu modelo ya usa el Facade 'PDF', así que lo usamos también

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
            'token_expires_at'    => now()->addDays(7),
            'signature_status'        => 'sent',
        ]);

        // 5. Enviar el email al cliente con el enlace de firma.
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
                      ->where('signature_status', 'sent')
                      ->where('token_expires_at', '>', now())
                      ->first();

        if (!$token) {
            // Si el token no es válido, mostramos un error.
            abort(404, 'Enlace de firma no válido, expirado o ya utilizado.');
        }
        
        // Carga la vista principal de Blade que monta tu aplicación de Vue.
        // Vue Router se encargará de mostrar el componente 'SigningPage'.
        return view('welcome');
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
                      ->firstOrFail(); // Lanza un 404 si no se encuentra.

        // 2. Validar que la firma viene en la petición.
        $request->validate(['signature' => 'required|string']);

        // 3. Decodificar y guardar la imagen de la firma.
        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->signature));
        $agreement = $token->agreement;
        $imageName = 'signature_' . $agreement->id . '_' . time() . '.png';
        $signaturePath = 'signatures/' . $imageName;
        Storage::disk('public')->put($signaturePath, $imageData);

        // --- LÓGICA DE REGENERACIÓN DEL PDF ADAPTADA A TU MODELO ---
        // 4. Regenerar el PDF, esta vez pasando la URL de la firma.
        $signedPdfPath = $this->regeneratePdfWithSignature($agreement, Storage::disk('public')->url($signaturePath));

        if (!$signedPdfPath) {
            // Manejar un posible error durante la generación del PDF.
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
    private function regeneratePdfWithSignature(Agreement $agreement, string $signatureUrl)
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
            'supplier.user.userDetail' // <-- CARGAMOS LA RELACIÓN COMPLETA
        ]);

        // 2. Determinar quién es el usuario creador.
        $creatorUser = null;
        if ($agreement->supplier && $agreement->supplier->user) {
            $creatorUser = $agreement->supplier->user;
        }
        // Si no hay proveedor, podrías añadir lógica para buscar un usuario admin aquí si fuera necesario.

        // 3. Preparar los datos para la vista, incluyendo el usuario y la firma.
        $data = [
            'agreement'     => $agreement,
            'user'          => $creatorUser, // Pasamos el usuario que encontramos
            'signature_url' => $signatureUrl
        ];
        $user = $creatorUser;
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
}