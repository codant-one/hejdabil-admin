<?php

namespace App\Http\Controllers;

use App\Models\Agreement;
use App\Models\Document;
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
        
        // Support both agreements and documents
        $pdfPath = $token->signed_pdf_path;
        
        if (!$pdfPath) {
            if ($token->agreement_id) {
                $agreement = $token->agreement;
                $pdfPath = $agreement->file;
            } elseif ($token->document_id) {
                $document = $token->document;
                $pdfPath = $document->file;
            }
        }
        
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
        ];
        
        // Support both agreements and documents
        if ($token->agreement_id) {
            $response['agreement_id'] = $token->agreement->agreement_id;
        } elseif ($token->document_id) {
            $response['document_id'] = $token->document->id;
            $response['document_title'] = $token->document->title;
        }
        
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
        
        // Support both agreements and documents
        $entityId = $token->agreement_id ?? $token->document_id;
        $entityType = $token->agreement_id ? 'agreement' : 'document';
        
        $imageName = 'signature_' . $entityType . '_' . $entityId . '_' . time() . '.png';
        $signaturePath = 'signatures/' . $imageName;
        Storage::disk('public')->put($signaturePath, $imageData);

        // 4. Regenerar el PDF con la firma
        $signedPdfPath = null;
        if ($token->agreement_id) {
            $agreement = $token->agreement;
            $signedPdfPath = $this->regeneratePdfWithSignature(
                $agreement,
                $signaturePath,
                $token->placement_x,
                $token->placement_y
            );
            if (!$signedPdfPath) {
                return response()->json(['message' => 'No se pudo regenerar el PDF con la firma.'], 500);
            }
            $agreement->file = $signedPdfPath;
            $agreement->save();
        } elseif ($token->document_id) {
            $document = $token->document;
            $signedPdfPath = $this->regenerateDocumentPdfWithSignature(
                $document,
                $signaturePath,
                $token->placement_x,
                $token->placement_y,
                $token->placement_page ?? 1
            );
            if (!$signedPdfPath) {
                return response()->json(['message' => 'No se pudo regenerar el PDF con la firma.'], 500);
            }
            $document->file = $signedPdfPath;
            $document->save();
        }

        // 5. Actualizar el registro del token con el estado 'signed' y las rutas de los archivos.
        $token->update([
            'signature_status' => 'signed',
            'signed_at' => now(),
            'signature_image_path' => $signaturePath,
            'signed_pdf_path' => $signedPdfPath,
        ]);

        // 6. Enviar email con el PDF firmado (adjuntar sólo si el archivo es pequeño)
        try {
            $recipientEmail = $token->recipient_email;
            if ($recipientEmail && $signedPdfPath) {
                $pdfFullPath = storage_path('app/public/' . $signedPdfPath);
                $downloadUrl = Storage::disk('public')->url($signedPdfPath);
                $attachFile = true;
                try {
                    $sizeBytes = Storage::disk('public')->size($signedPdfPath);
                    // adjuntar sólo si < 4.5MB para evitar límites de 5MB
                    if ($sizeBytes !== null && $sizeBytes > 4500000) {
                        $attachFile = false;
                    }
                } catch (\Throwable $t) {
                    // si falla size, intentamos adjuntar
                    $attachFile = true;
                }

                if ($token->agreement_id) {
                    $agreement = $token->agreement;
                    Mail::to($recipientEmail)->send(new SignedDocumentMail($agreement, $attachFile ? $pdfFullPath : '', null, $downloadUrl, $attachFile));
                } elseif ($token->document_id) {
                    $document = $token->document;
                    Mail::to($recipientEmail)->send(new SignedDocumentMail(null, $attachFile ? $pdfFullPath : '', $document, $downloadUrl, $attachFile));
                }
            }
        } catch (\Exception $e) {
            Log::error('Kunde inte skicka signerat PDF via e-post: ' . $e->getMessage());
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
            $configSignature   = Config::getByKey('signature')    ?? ['value' => '[]'];

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
            $signatureRaw    = $getValue($configSignature);

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
            $signatureObj    = $decodeSafe($signatureRaw);
            
            $company->logo = $logoObj->logo ?? null;
            $company->img_signature = $signatureObj->img_signature ?? null;
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
        
        // Support both agreements and documents
        if ($token->agreement_id) {
            $agreement = $token->agreement;
            if ($agreement && $agreement->file && Storage::disk('public')->exists($agreement->file)) {
                $path = storage_path('app/public/' . $agreement->file);
                return response()->file($path);
            }
        } elseif ($token->document_id) {
            $document = $token->document;
            if ($document && $document->file && Storage::disk('public')->exists($document->file)) {
                $path = storage_path('app/public/' . $document->file);
                return response()->file($path);
            }
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
        $alignment = $token->signature_alignment ?? 'left'; // Usar el valor guardado o 'left' por defecto
        
        // Si es un agreement, usar la lógica original
        if ($token->agreement_id && $token->agreement) {
            $agreementTypeId = $token->agreement->agreement_type_id;
            if ($agreementTypeId == 2) { // 2 = Purchase
                $alignment = 'right';
            }
        }

        return response()->json([
            'placement_x' => $token->placement_x,
            'placement_y' => $token->placement_y,
            'placement_page' => $token->placement_page,
            'signature_alignment' => $alignment,
        ]);
    }
    
    /**
     * Helper privado para regenerar el PDF de un documento con la firma.
     * Usa FPDI para agregar la imagen de la firma al PDF existente.
     */
    private function regenerateDocumentPdfWithSignature(Document $document, string $signatureUrl, ?float $x, ?float $y, int $targetPage = 1)
    {
        // Leer el PDF original
        $originalPdfPath = storage_path('app/public/' . $document->file);
        
        if (!file_exists($originalPdfPath)) {
            return null;
        }

        // Asegurar que el directorio existe
        $signedDir = storage_path('app/public/documents/signed');
        if (!file_exists($signedDir)) {
            Storage::disk('public')->makeDirectory('documents/signed');
        }

        // Ruta de la imagen de la firma
        $signatureImagePath = Storage::disk('public')->path($signatureUrl);
        
        if (!file_exists($signatureImagePath)) {
            return null;
        }

        // Nombre del archivo firmado
        $fileName = 'document-' . $document->id . '-signed-' . time() . '.pdf';
        $filePath = 'documents/signed/' . $fileName;
        $destinationPath = storage_path('app/public/' . $filePath);

        try {
            // Intentar usar FPDI si está disponible
            if (class_exists('\\setasign\\Fpdi\\Fpdi')) {
                return $this->addSignatureWithFPDI($originalPdfPath, $signatureImagePath, $destinationPath, $x, $y, $filePath, $targetPage);
            }
            
            // Si FPDI no está disponible, intentar usar Imagick como alternativa
            // Solo si está realmente disponible (no solo cargado en php.ini)
            if (extension_loaded('imagick') && class_exists('Imagick')) {
                return $this->addSignatureWithImagick($originalPdfPath, $signatureImagePath, $destinationPath, $x, $y, $filePath, $targetPage);
            }
            
            // Si ninguna librería está disponible, lanzar excepción
            throw new \Exception('FPDI es requerido para agregar firmas a PDFs. Por favor instala setasign/fpdi ejecutando: composer require setasign/fpdi');
            
        } catch (\Exception $e) {
            Log::error('Error al agregar firma al PDF: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Agrega la firma usando FPDI
     */
    private function addSignatureWithFPDI($pdfPath, $signaturePath, $outputPath, $x, $y, $filePath, int $targetPage = 1)
    {
        $pdf = new \setasign\Fpdi\Fpdi();
        
        // Obtener el número de páginas del PDF original
        $pageCount = $pdf->setSourceFile($pdfPath);
        
        // Procesar todas las páginas
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            // Importar la página
            $pageId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($pageId);
            
            // Agregar la página
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($pageId);
            
            // Agregar la firma en la página objetivo
            if ($pageNo === $targetPage) {
                // Convertir coordenadas de porcentaje a la unidad del PDF (mm por defecto)
                $xPoint = ($x / 100) * $size['width'];
                $yPoint = ($y / 100) * $size['height'];

                // Calcular tamaño de firma: 20% del ancho de página, manteniendo proporción real de la imagen
                $sigWidth = max(5, $size['width'] * 0.20);
                $sigHeight = 0; // se calculará por proporción
                if (function_exists('getimagesize')) {
                    $imgSize = @getimagesize($signaturePath);
                    if (is_array($imgSize) && !empty($imgSize[0]) && !empty($imgSize[1]) && $imgSize[0] > 0) {
                        $ratio = $imgSize[1] / $imgSize[0];
                        $sigHeight = $sigWidth * $ratio;
                    }
                }
                if ($sigHeight === 0) {
                    // fallback a una altura aproximada si no se pudo leer la imagen
                    $sigHeight = $sigWidth * 0.4;
                }

                // Dibujar usando el punto clicado como esquina superior izquierda
                $xDraw = $xPoint;
                $yDraw = $yPoint;

                // Limitar para que no se salga de la página
                $xDraw = max(0, min($size['width'] - $sigWidth, $xDraw));
                $yDraw = max(0, min($size['height'] - $sigHeight, $yDraw));
                
                // Dibujar imagen
                $pdf->Image($signaturePath, $xDraw, $yDraw, $sigWidth, $sigHeight);
            }
        }
        
        // Guardar el PDF
        $pdf->Output('F', $outputPath);
        
        return $filePath;
    }

    /**
     * Agrega la firma usando Imagick (alternativa si FPDI no está disponible)
     */
    private function addSignatureWithImagick($pdfPath, $signaturePath, $outputPath, $x, $y, $filePath, int $targetPage = 1)
    {
        // Leer el PDF con Imagick
        $pdf = new \Imagick();
        $pdf->setResolution(150, 150);
        $pdf->readImage($pdfPath);
        
        // Obtener la página objetivo (0-index)
        $index = max(0, $targetPage - 1);
        $pdf->setIteratorIndex($index);
        $page = $pdf->getImage();
        
        // Leer la imagen de la firma
        $signature = new \Imagick($signaturePath);

        // Obtener dimensiones de la página
        $pageWidth = $page->getImageWidth();
        $pageHeight = $page->getImageHeight();

        // Calcular tamaño de firma: 20% del ancho de página, mantener proporción real
        $sigWidthPx = (int) max(30, $pageWidth * 0.20);
        $sigRatio = 0.4;
        try {
            $sigRatio = $signature->getImageHeight() > 0 ? ($signature->getImageHeight() / $signature->getImageWidth()) : 0.4;
        } catch (\Exception $e) {
            $sigRatio = 0.4;
        }
        $sigHeightPx = (int) max(15, $sigWidthPx * $sigRatio);
        $signature->resizeImage($sigWidthPx, $sigHeightPx, \Imagick::FILTER_LANCZOS, 1);
        
        // Convertir coordenadas de porcentaje a píxeles
        $xPixel = ($x / 100.0) * $pageWidth;
        $yPixel = ($y / 100.0) * $pageHeight;
        
        // Usar el punto clicado como esquina superior izquierda
        $xDraw = (int) round($xPixel);
        $yDraw = (int) round($yPixel);

        // Limitar para no salir de la página
        $xDraw = max(0, min($pageWidth - $sigWidthPx, $xDraw));
        $yDraw = max(0, min($pageHeight - $sigHeightPx, $yDraw));
        
        // Componer la firma sobre la página
        $page->compositeImage($signature, \Imagick::COMPOSITE_OVER, $xDraw, $yDraw);
        
        // Volver a colocar la página modificada
        $pdf->setImage($page);
        $pdf->writeImages($outputPath, true);
        
        // Limpiar
        $signature->destroy();
        $page->destroy();
        $pdf->destroy();
        
        return $filePath;
    }
}