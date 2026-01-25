<?php

namespace App\Http\Controllers;

use App\Http\Requests\Signature\SendSignatureRequest;
use App\Http\Requests\Signature\SendStaticSignatureRequest;
use App\Http\Requests\Signature\SignatureRequest;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Log;

use App\Mail\SignedDocumentMail;

use PDF;

use App\Models\UserDetails;
use App\Models\User;
use App\Models\Config;
use App\Models\Agreement;
use App\Models\Document;
use App\Models\Token;
use App\Models\TokenHistory;

class SignatureController extends Controller
{
    /**
     * Correction factor for the Y position of the signature in documents.
     * Compensates for the difference between the rendering of vue-pdf-embed (PDF.js) and the actual coordinates of the PDF.
     * Value expressed as a percentage of the page height (e.g., 0.045 = 4.5%).
     * Positive value = move the signature up in the final PDF.
     * Adjust this value if the signature appears vertically offset.
     */
    private const SIGNATURE_Y_CORRECTION_FACTOR = 0.025; // 2.5% upward adjustment for desktop
    private const SIGNATURE_Y_CORRECTION_FACTOR_MOBILE = 0.045; // 4.5% upward adjustment for mobile

    /**
     * Detect if the request comes from a mobile device
     */
    private function isMobileDevice($userAgent)
    {
        if (empty($userAgent)) {
            return false;
        }
        
        return preg_match('/(android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini)/i', $userAgent);
    }

    /**
     * Method 1: Start the signing process (Triggered from your Vue Dashboard).
     * URL: POST /api/agreements/{agreement}/send-signature-request
     */
    public function sendSignatureRequest(Agreement $agreement, SendSignatureRequest $request)
    {
        $validated = $request->validated();
  
        // 1. Check if the agreement already has a generated PDF.
        if (!$agreement->file) {
            return response()->json([
                'message' => 'Det finns ännu ingen PDF-fil för detta avtal som kan undertecknas.'
            ], 422);
        }

        // 2. Get the client associated with the agreement. Your model uses 'agreement_client'.
        $agreementClient = $agreement->agreement_client;
        if (!$agreementClient || !$agreementClient->email) {
            return response()->json([
                'message' => 'Det finns ingen kund med en associerad e-postadress för detta avtal.'
            ], 422);
        }

        // 3. Get or create token for this agreement
        // First check for tokens with 'created' or 'delivery_issues' status
        $token = $agreement->tokens()
            ->whereIn('signature_status', ['created', 'delivery_issues'])
            ->latest()
            ->first();

        if (!$token) {
            // If no 'created' or 'delivery_issues' token exists, create a new one
            $signingToken = Str::uuid()->toString();
            
            // 4. Create the record in the 'tokens' table.
            $token = $agreement->tokens()->create([
                'signing_token' => $signingToken,
                'recipient_email'     => $validated['email'],
                'token_expires_at'    => now()->addDays(7),
                'signature_status'        => 'created',
                'placement_x'   => $validated['x'],
                'placement_y'   => $validated['y'],
                'placement_page'=> $validated['page'],
            ]);

            // Register 'created' event
            TokenHistory::logEvent(
                tokenId: $token->id,
                eventType: TokenHistory::EVENT_CREATED,
                description: 'Avtal skapat och sparat i systemet',
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
                metadata: [
                    'recipient' => $validated['email'],
                    'agreement_id' => $agreement->id,
                ]
            );
        } else {
            // Update existing token with new signature details
            $token->update([
                'recipient_email'     => $validated['email'],
                'token_expires_at'    => now()->addDays(7),
                'placement_x'   => $validated['x'],
                'placement_y'   => $validated['y'],
                'placement_page'=> $validated['page'],
            ]);
        }

        // 5. Send the email to the customer with the signature link.
        try {
            // Validate that the email exists and is valid
            if (!filter_var($validated['email'], FILTER_VALIDATE_EMAIL)) {
                $token->update(['signature_status' => 'delivery_issues']);
                
                TokenHistory::logEvent(
                    tokenId: $token->id,
                    eventType: TokenHistory::EVENT_DELIVERY_ISSUES,
                    description: 'Ogiltig e-postadress.',
                    ipAddress: $request->ip(),
                    userAgent: $request->userAgent(),
                    metadata: ['recipient' => $validated['email'], 'error' => 'Invalid email format']
                );
                
                return response()->json([
                    'message' => 'Ogiltig e-postadress.'
                ], 422);
            }

            // Update status to ‘sent’ before attempting to send
            $token->update(['signature_status' => 'sent']);
            
            // Register 'sent' event
            TokenHistory::logEvent(
                tokenId: $token->id,
                eventType: TokenHistory::EVENT_SENT,
                description: 'E-post skickad till ' . $validated['email'],
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
                metadata: ['recipient' => $validated['email']]
            );

            $signingUrl = env('APP_DOMAIN') . '/sign/' . $token->signing_token;
            $clientEmail = $validated['email'];
            $subject = 'Solicitud para Firmar su Contrato';

            \Mail::send(
                'emails.agreements.signature_request'
                , ['signingUrl' => $signingUrl]
                , function ($message) use ($clientEmail, $subject) {
                    $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
                    $message->to($clientEmail)->subject($subject);
            });
            
            // Update status to delivered if the sending was successful
            $token->update(['signature_status' => 'delivered']);
            
            // Log 'delivered' event
            TokenHistory::logEvent(
                tokenId: $token->id,
                eventType: TokenHistory::EVENT_DELIVERED,
                description: 'Signature request email delivered successfully',
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
                metadata: ['recipient' => $validated['email']]
            );
            
            return response()->json([
                'message' => 'Begäran om underskrift skickad med framgång.'
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error sending signature email: ' . $e->getMessage(), [
                'agreement_id' => $agreement->id,
                'email' => $validated['email'],
                'error_type' => get_class($e)
            ]);
            $token->update(['signature_status' => 'delivery_issues']);
            
            // Log 'delivery_issues' event
            TokenHistory::logEvent(
                tokenId: $token->id,
                eventType: TokenHistory::EVENT_DELIVERY_ISSUES,
                description: 'Email sending failed',
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
                metadata: ['error' => $e->getMessage(), 'error_type' => get_class($e), 'recipient' => $validated['email']]
            );
            
            return response()->json([
                'message' => 'Det gick inte att skicka e-postmeddelandet. Kontrollera e-postadressen och försök igen.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Sign with a fixed position.
    public function sendStaticSignatureRequest(Agreement $agreement, SendStaticSignatureRequest $request)
    {
        $validated = $request->validated();

        // 1. Check if the agreement already has a generated PDF.
        if (!$agreement->file) {
            return response()->json([
                'message' => 'Det finns ännu ingen PDF-fil för detta avtal som kan undertecknas.'
            ], 422);
        }

        // 2. Get the client associated with the agreement.
        $agreementClient = $agreement->agreement_client;
        if (!$agreementClient || !$agreementClient->email) {
            return response()->json([
                'message' => 'Det finns ingen kund med en associerad e-postadress för detta avtal.'
            ], 422);
        }

        // 3. Get or create token for this agreement
        // First check for tokens with 'created' or 'delivery_issues' status
        $token = $agreement->tokens()
            ->whereIn('signature_status', ['created', 'delivery_issues'])
            ->latest()
            ->first();

        if (!$token) {
            // If no 'created' or 'delivery_issues' token exists, create a new one
            $signingToken = Str::uuid()->toString();
            
            // 4. Create the record in 'tokens' WITHOUT coordinates.
            $token = $agreement->tokens()->create([
                'signing_token' => $signingToken,
                'recipient_email'     => $validated['email'],
                'token_expires_at'    => now()->addDays(7),
                'signature_status'        => 'created',
                'placement_x'   => null, // Save null to identify static signature
                'placement_y'   => null,
                'placement_page'=> 1,    // Default to page 1
            ]);

            // Log 'created' event when token is created
            TokenHistory::logEvent(
                tokenId: $token->id,
                eventType: TokenHistory::EVENT_CREATED,
                description: 'Signature token created for agreement (static)',
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
                metadata: [
                    'agreement_id' => $agreement->id,
                    'recipient' => $validated['email'],
                    'static_signature' => true,
                ]
            );
        } else {
            // Update existing token with new signature details
            $token->update([
                'recipient_email'     => $validated['email'],
                'token_expires_at'    => now()->addDays(7),
                'placement_x'   => null,
                'placement_y'   => null,
                'placement_page'=> 1,
            ]);
        }

        // 5. Send the email to the client.
        try {
            // Validate that the email exists and is valid
            if (!filter_var($validated['email'], FILTER_VALIDATE_EMAIL)) {
                $token->update(['signature_status' => 'delivery_issues']);
                
                // Log delivery issues event
                TokenHistory::logEvent(
                    tokenId: $token->id,
                    eventType: TokenHistory::EVENT_DELIVERY_ISSUES,
                    description: 'Invalid email address',
                    ipAddress: $request->ip(),
                    userAgent: $request->userAgent(),
                    metadata: ['recipient' => $validated['email'], 'error' => 'Invalid email format']
                );
                
                return response()->json([
                    'message' => 'Ogiltig e-postadress.'
                ], 422);
            }

            // Update status to 'sent'
            $token->update(['signature_status' => 'sent']);

            // Update status to 'sent' before attempting to send
            $token->update(['signature_status' => 'sent']);
            
            // Log 'sent' event
            TokenHistory::logEvent(
                tokenId: $token->id,
                eventType: TokenHistory::EVENT_SENT,
                description: 'Static signature request email sent to ' . $validated['email'],
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
                metadata: ['recipient' => $validated['email']]
            );

            $signingUrl = env('APP_DOMAIN') . '/sign/' . $token->signing_token;
            $clientEmail = $validated['email'];
            $subject = 'Solicitud para Firmar su Contrato';

            \Mail::send(
                'emails.agreements.signature_request'
                , ['signingUrl' => $signingUrl]
                , function ($message) use ($clientEmail, $subject) {
                    $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
                    $message->to($clientEmail)->subject($subject);
            });
            
            // Update status to 'delivered' if the sending was successful
            $token->update(['signature_status' => 'delivered']);
            
            // Log 'delivered' event
            TokenHistory::logEvent(
                tokenId: $token->id,
                eventType: TokenHistory::EVENT_DELIVERED,
                description: 'Static signature request email delivered successfully',
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
                metadata: ['recipient' => $validated['email']]
            );
            
            return response()->json(['message' => 'Begäran om underskrift skickad med framgång.']);
        } catch (\Exception $e) {
            \Log::error('Error sending static signature email for document: ' . $e->getMessage());
            $token->update(['signature_status' => 'delivery_issues']);
            
            // Log 'delivery_issues' event
            TokenHistory::logEvent(
                tokenId: $token->id,
                eventType: TokenHistory::EVENT_DELIVERY_ISSUES,
                description: 'Email sending failed',
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
                metadata: ['error' => $e->getMessage(), 'error_type' => get_class($e), 'recipient' => $validated['email']]
            );
            
            return response()->json([
                'message' => 'Det gick inte att skicka e-postmeddelandet. Kontrollera e-postadressen och försök igen.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Method 2: Show the signing page (Activated when the client opens the link).
     * URL: GET /sign/{token}
     */
    public function showSigningPage($tokenString)
    {
        // Validate that the token is correct, pending, and not expired.
        $token = Token::where('signing_token', $tokenString)
                  ->where('token_expires_at', '>', now())
                  ->first();

        if (!$token) {
            abort(404, 'Ogiltig eller utgången signaturlänk');
        }

        // Check if the token has already been used to sign
        if ($token->signature_status === 'signed') {
            return view('welcome');
        }

        // If the token is in 'sent', 'delivered', or 'reviewed' status, proceed normally
        // and update the status to 'reviewed' if it is not already
        if (in_array($token->signature_status, ['sent', 'delivered', 'reviewed'])) {
            // Update to reviewed if it is not already
            if ($token->signature_status !== 'reviewed') {
                $token->update(['signature_status' => 'reviewed']);
            }
            return view('welcome');
        }
        
        // For any other unhandled status
        abort(404, 'Ogiltigt signaturstatus.');
    }

    /**
     * Get the signed PDF if the token corresponds to an already signed document
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
        
        abort(404, 'Signerad PDF-fil hittades inte.');
    }

    /**
     * Get the token status and additional details
     * URL: GET /api/signatures/{token}/status
     */
    public function getTokenStatus($tokenString)
    {
        $token = Token::where('signing_token', $tokenString)->first();
        
        if (!$token) {
            return response()->json([
                'status' => 'invalid',
                'message' => 'Ogiltig token'
            ], 404);
        }
        
        if ($token->token_expires_at < now() && $token->signature_status !== 'signed') {
            return response()->json([
                'status' => 'expired',
                'message' => 'Signeringslänken har löpt ut'
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
     * Log a visit to the signing page (event 'reviewed')
     * URL: POST /api/signatures/{token}/log-view
     */
    public function logView(Request $request, $tokenString)
    {
        $token = Token::where('signing_token', $tokenString)->first();
        
        if (!$token) {
            return response()->json([
                'status' => 'invalid',
                'message' => 'Ogiltig token'
            ], 404);
        }

        // Register the event in the history
        TokenHistory::logEvent(
            tokenId: $token->id,
            eventType: TokenHistory::EVENT_REVIEWED,
            description: 'Användaren gick in på signeringssidan',
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
            metadata: [
                'referer' => $request->header('referer'),
                'timestamp' => now()->toIso8601String(),
            ]
        );

        // Update the token status to 'reviewed' if it is not already signed
        if (in_array($token->signature_status, ['sent', 'delivered'])) {
            $token->update([
                'signature_status' => 'reviewed',
                'viewed_at' => now(),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Visning registrerad framgångsrikt'
        ]);
    }

    /**
     * Method 3: Save the signature and regenerate the PDF (activated from the Vue signature page
     * URL: POST /api/signatures/submit/{token}
     */
    public function storeSignature(SignatureRequest $request, $tokenString)
    {
        try {
            // 1. Validate the token again for maximum security.
            $token = Token::where('signing_token', $tokenString)
                          ->whereIn('signature_status', ['sent', 'delivered', 'reviewed'])
                          ->where('token_expires_at', '>', now())
                          ->firstOrFail();
            // 2. Ensure the signature is present in the request (already validated by SignatureRequest).

            // 3. Decode and save the signature image.
            $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->signature));
            
            // Support both agreements and documents
            $entityId = $token->agreement_id ?? $token->document_id;
            $entityType = $token->agreement_id ? 'agreement' : 'document';
            
            $imageName = 'signature_' . $entityType . '_' . $entityId . '_' . time() . '.png';
            $signaturePath = 'signatures/' . $imageName;
            Storage::disk('public')->put($signaturePath, $imageData);

            // 4. Regenerate the PDF with the signature
            $signedPdfPath = null;
            $userAgent = $request->userAgent();
            
            if ($token->agreement_id) {
                $agreement = $token->agreement;
                $signedPdfPath = $this->regeneratePdfWithSignature(
                    $agreement,
                    $signaturePath,
                    $token->placement_x,
                    $token->placement_y,
                    $userAgent
                );

                if (!$signedPdfPath) {
                    // Change status to failed if the PDF cannot be regenerated
                    $token->update(['signature_status' => 'failed']);
                    return response()->json([
                        'message' => 'Det gick inte att återställa PDF-filen med signaturen.'
                    ], 500);
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
                    $token->placement_page ?? 1,
                    $userAgent
                );
                if (!$signedPdfPath) {
                    // Change status to failed if the PDF cannot be regenerated
                    $token->update(['signature_status' => 'failed']);
                    return response()->json([
                        'message' => 'Det gick inte att återställa dokument-PDF-filen med signaturen.'
                    ], 500);
                }
                $document->file = $signedPdfPath;
                $document->save();
            }

            // 5. Update the token record with the 'signed' status and file paths.
            $token->update([
                'signature_status' => 'signed',
                'signed_at' => now(),
                'signature_image_path' => $signaturePath,
                'signed_pdf_path' => $signedPdfPath,
            ]);

            // Register successful signature event in history
            TokenHistory::logEvent(
                tokenId: $token->id,
                eventType: TokenHistory::EVENT_SIGNED,
                description: 'Dokumentet har undertecknats framgångsrikt',
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
                metadata: [
                    'signed_pdf_path' => $signedPdfPath,
                    'signature_image_path' => $signaturePath,
                ]
            );

            // 6. Send email with the signed PDF (attach only if the file is small)
            try {
                $recipientEmail = $token->recipient_email;
                if ($recipientEmail && $signedPdfPath) {
                    $pdfFullPath = storage_path('app/public/' . $signedPdfPath);
                    $downloadUrl = Storage::disk('public')->url($signedPdfPath);
                    $attachFile = true;
                    try {
                        $sizeBytes = Storage::disk('public')->size($signedPdfPath);
                        // attach only if < 4.5MB to avoid 5MB limits
                        if ($sizeBytes !== null && $sizeBytes > 4500000) {
                            $attachFile = false;
                        }
                    } catch (\Throwable $t) {
                        // if size check fails, attempt to attach
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
            
            // 7. Return a successful response to Vue.
            $userId = null;
            if ($token->agreement_id) {
                $agreement = $token->agreement;
                // Get the user_id of the supplier associated with the agreement
                if ($agreement->supplier && $agreement->supplier->user_id) {
                    $userId = $agreement->supplier->user_id;
                }
            } elseif ($token->document_id) {
                $document = $token->document;
                // If documents have user_id or supplier
                if (isset($document->user_id)) {
                    $userId = $document->user_id;
                } elseif (isset($document->supplier) && $document->supplier->user_id) {
                    $userId = $document->supplier->user_id;
                }
            }
            
            $response = [
                'message'      => 'Kontraktet har undertecknats.',
                'download_url' => Storage::disk('public')->url($signedPdfPath),
                'signed_by'    => $token->recipient_email,
                'user_id'      => $userId, // ID del usuario que debe recibir la notificación
            ];

            // Add document or agreement ID for download
            if ($token->agreement_id) {
                $response['agreement_id'] = $token->agreement_id;
            } elseif ($token->document_id) {
                $response['document_id'] = $token->document_id;
            }

            return response()->json($response);
            
        } catch (\Exception $e) {
            // Any 500 error during the signing process changes the status to failed
            Log::error('Error during the signing process: ' . $e->getMessage(), [
                'token' => $tokenString,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Update the token status to failed
            if (isset($token)) {
                $token->update(['signature_status' => 'failed']);
                
                // Register failure event in history
                TokenHistory::logEvent(
                    tokenId: $token->id,
                    eventType: TokenHistory::EVENT_FAILED,
                    description: 'Fel under signeringsprocessen: ' . $e->getMessage(),
                    ipAddress: $request->ip(),
                    userAgent: $request->userAgent(),
                    metadata: [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]
                );
            }
            
            return response()->json([
                'message' => 'Ett oväntat fel uppstod under signeringsprocessen. Vänligen försök igen senare.'
            ], 500);
        }
    }
    
    /**
     * Private helper to regenerate the PDF with the signature.
     * This function adapts the logic from your Agreement::generatePdf() method.
     */
    private function regeneratePdfWithSignature(Agreement $agreement, string $signatureUrl, ?float $x, ?float $y, ?string $userAgent = null)
    {
        // 1. Load all necessary relationships.
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

        // 2. Determine who the creator user is.
        $creatorUser = null;
        if ($agreement->supplier && $agreement->supplier->user) {
            $creatorUser = $agreement->supplier->user;
        }
        // If there is no supplier, you could add logic to find an admin user here if needed.
        $fullPath = Storage::disk('public')->path($signatureUrl);
        $imageData = base64_encode(file_get_contents($fullPath));
        $signatureImageSrc = 'data:image/png;base64,' . $imageData;

        // 3. Prepare data for the view, including the user and the signature.
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

            // Extract the "value" supporting array or object
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

        // 4. Determine the view and file name (this part was already fine).
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

        // 5. Generate and save the new signed PDF.
        $filePath = 'pdfs/' . $fileName;
        PDF::loadView($viewName, $data)->save(storage_path('app/public/' . $filePath));
        
        return $filePath;
    }

    public function getUnsignedPdf($tokenString)
    {
        $token = Token::where('signing_token', $tokenString)->firstOrFail();

        // Mark view if not already recorded
        if (is_null($token->viewed_at)) {
            $token->viewed_at = now();
            try { $token->save(); } catch (\Throwable $e) { /* soft log if you want */ }
        }
        
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
        
        abort(404, 'PDF-fil hittades inte.');
    }

    public function getAdminPreviewPdf(Agreement $agreement)
    {
        
        if ($agreement && $agreement->file && Storage::disk('public')->exists($agreement->file)) {
            $path = storage_path('app/public/' . $agreement->file);
            return response()->file($path);
        }

        abort(404, 'PDF-fil hittades inte för detta kontrakt.');
    }

    public function getSignatureDetails($tokenString)
    {
        $token = Token::where('signing_token', $tokenString)->firstOrFail();

        // Logic to determine the signature alignment
        $alignment = $token->signature_alignment ?? 'left'; // Use the saved value or 'left' by default
        
        // If it's an agreement, use the original logic
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
     * Private helper to regenerate the PDF of a document with the signature.
     * Uses FPDI to add the signature image to the existing PDF.
     */
    private function regenerateDocumentPdfWithSignature(Document $document, string $signatureUrl, ?float $x, ?float $y, int $targetPage = 1, ?string $userAgent = null)
    {
        // Read the original PDF
        $originalPdfPath = storage_path('app/public/' . $document->file);
        
        if (!file_exists($originalPdfPath)) {
            return null;
        }

        // Ensure the directory exists
        $signedDir = storage_path('app/public/documents/signed');
        if (!file_exists($signedDir)) {
            Storage::disk('public')->makeDirectory('documents/signed');
        }

        // Path to the signature image
        $signatureImagePath = Storage::disk('public')->path($signatureUrl);
        
        if (!file_exists($signatureImagePath)) {
            return null;
        }

        // Signed file name
        $fileName = 'document-' . $document->id . '-signed-' . time() . '.pdf';
        $filePath = 'documents/signed/' . $fileName;
        $destinationPath = storage_path('app/public/' . $filePath);

        try {
            // Try to use FPDI if available
            if (class_exists('\\setasign\\Fpdi\\Fpdi')) {
                return $this->addSignatureWithFPDI($originalPdfPath, $signatureImagePath, $destinationPath, $x, $y, $filePath, $targetPage, $userAgent);
            }
            
            // If FPDI is not available, try using Imagick as an alternative
            // Only if it is really available (not just loaded in php.ini)
            if (extension_loaded('imagick') && class_exists('Imagick')) {
                return $this->addSignatureWithImagick($originalPdfPath, $signatureImagePath, $destinationPath, $x, $y, $filePath, $targetPage, $userAgent);
            }
            
            // If no library is available, throw an exception
            throw new \Exception('FPDI is required to add signatures to PDF files. Install setasign/fpdi by running: composer require setasign/fpdi');
            
        } catch (\Exception $e) {
            Log::error('Error adding signature to PDF: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Adds the signature using FPDI
     */
    private function addSignatureWithFPDI($pdfPath, $signaturePath, $outputPath, $x, $y, $filePath, int $targetPage = 1, ?string $userAgent = null)
    {
        $pdf = new \setasign\Fpdi\Fpdi();
        
        // Get the number of pages in the original PDF
        $pageCount = $pdf->setSourceFile($pdfPath);
        
        // Detect if request comes from mobile device
        $isMobile = $this->isMobileDevice($userAgent);
        
        // Process all pages
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            // Import the page
            $pageId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($pageId);
            
            // Add the page
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($pageId);
            
            // Add the signature on the target page
            if ($pageNo === $targetPage) {
                // Detect PDF orientation (landscape vs portrait)
                $isLandscape = $size['width'] > $size['height'];
                
                // Apply same scale factors as frontend button
                // These factors match the visual size of the button shown to users
                $scaleFactor = $isLandscape ? 0.4 : 0.7; // 40% for landscape, 70% for portrait
                
                // Convert coordinates from percentage to PDF units (mm by default)
                $xPoint = ($x / 100) * $size['width'];
                $yPoint = ($y / 100) * $size['height'];

                // CORRECTION: Adjust the Y coordinate to compensate for the difference 
                // between the rendering of vue-pdf-embed and the actual PDF coordinates.
                // Apply different correction based on device: 4.5% for mobile, 2.5% for desktop
                $yCorrectionFactor = $isMobile ? self::SIGNATURE_Y_CORRECTION_FACTOR_MOBILE : self::SIGNATURE_Y_CORRECTION_FACTOR;
                $yCorrection = $size['height'] * $yCorrectionFactor;
                $yPoint = max(0, $yPoint - $yCorrection);

                // Calculate signature size: 35% of page width * scale factor, maintaining the real image proportion
                $sigWidth = max(5, $size['width'] * 0.35 * $scaleFactor);
                $sigHeight = 0; // will be calculated by proportion
                if (function_exists('getimagesize')) {
                    $imgSize = @getimagesize($signaturePath);
                    if (is_array($imgSize) && !empty($imgSize[0]) && !empty($imgSize[1]) && $imgSize[0] > 0) {
                        $ratio = $imgSize[1] / $imgSize[0];
                        $sigHeight = $sigWidth * $ratio;
                    }
                }
                if ($sigHeight === 0) {
                    // fallback to an approximate height if the image could not be read
                    $sigHeight = $sigWidth * 0.4;
                }

                // The clicked point represents where the user wants the signature to appear
                // Draw with the top-left corner at that point
                // DO NOT center because the visual placeholder in the frontend also uses the top-left corner
                $xDraw = $xPoint;
                $yDraw = $yPoint;

                // Limit so it does not go off the page
                $xDraw = max(0, min($size['width'] - $sigWidth, $xDraw));
                $yDraw = max(0, min($size['height'] - $sigHeight, $yDraw));
                
                // Draw image
                $pdf->Image($signaturePath, $xDraw, $yDraw, $sigWidth, $sigHeight);
            }
        }
        
        // Save the PDF
        $pdf->Output('F', $outputPath);
        
        return $filePath;
    }

    /**
     * Adds the signature using Imagick (alternative if FPDI is not available)
     */
    private function addSignatureWithImagick($pdfPath, $signaturePath, $outputPath, $x, $y, $filePath, int $targetPage = 1, ?string $userAgent = null)
    {
        // Read the PDF with Imagick
        $pdf = new \Imagick();
        $pdf->setResolution(150, 150);
        $pdf->readImage($pdfPath);
        
        // Get the target page (0-index)
        $index = max(0, $targetPage - 1);
        $pdf->setIteratorIndex($index);
        $page = $pdf->getImage();
        
        // Read the signature image
        $signature = new \Imagick($signaturePath);

        // Get page dimensions
        $pageWidth = $page->getImageWidth();
        $pageHeight = $page->getImageHeight();

        // Detect PDF orientation (landscape vs portrait)
        $isLandscape = $pageWidth > $pageHeight;
        
        // Detect if request comes from mobile device
        $isMobile = $this->isMobileDevice($userAgent);
        
        // Apply same scale factors as frontend button
        // These factors match the visual size of the button shown to users
        $scaleFactor = $isLandscape ? 0.4 : 0.7; // 40% for landscape, 70% for portrait

        // Calculate signature size: 35% of page width * scale factor, maintaining the real image proportion
        $sigWidthPx = (int) max(30, $pageWidth * 0.35 * $scaleFactor);
        $sigRatio = 0.4;
        try {
            $sigRatio = $signature->getImageHeight() > 0 ? ($signature->getImageHeight() / $signature->getImageWidth()) : 0.4;
        } catch (\Exception $e) {
            $sigRatio = 0.4;
        }
        $sigHeightPx = (int) max(15, $sigWidthPx * $sigRatio);
        $signature->resizeImage($sigWidthPx, $sigHeightPx, \Imagick::FILTER_LANCZOS, 1);
        
        // Convert coordinates from percentage to pixels
        $xPixel = ($x / 100.0) * $pageWidth;
        $yPixel = ($y / 100.0) * $pageHeight;

        // CORRECTION: Adjust the Y coordinate to compensate for the difference 
        // between the rendering of vue-pdf-embed and the actual PDF coordinates.
        // Apply different correction based on device: 4.5% for mobile, 2.5% for desktop
        $yCorrectionFactor = $isMobile ? self::SIGNATURE_Y_CORRECTION_FACTOR_MOBILE : self::SIGNATURE_Y_CORRECTION_FACTOR;
        $yCorrection = $pageHeight * $yCorrectionFactor;
        $yPixel = max(0, $yPixel - $yCorrection);
        
        // Use the clicked point as the top-left corner
        $xDraw = (int) round($xPixel);
        $yDraw = (int) round($yPixel);

        // Limit so it does not go off the page
        $xDraw = max(0, min($pageWidth - $sigWidthPx, $xDraw));
        $yDraw = max(0, min($pageHeight - $sigHeightPx, $yDraw));
        
        // Composite the signature onto the page
        $page->compositeImage($signature, \Imagick::COMPOSITE_OVER, $xDraw, $yDraw);
        
        // Set the modified page back
        $pdf->setImage($page);
        $pdf->writeImages($outputPath, true);
        
        // Clean up
        $signature->destroy();
        $page->destroy();
        $pdf->destroy();
        
        return $filePath;
    }
}