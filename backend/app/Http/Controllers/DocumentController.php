<?php

namespace App\Http\Controllers;

use App\Http\Requests\DocumentRequest;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

use Spatie\Permission\Middlewares\PermissionMiddleware;

use App\Models\Document;
use App\Models\Token;
use App\Models\Supplier;

class DocumentController extends Controller
{

    public function __construct()
    {
        $this->middleware(PermissionMiddleware::class . ':view signed-documents|administrator')->only(['index']);
        $this->middleware(PermissionMiddleware::class . ':create signed-documents|administrator')->only(['store']);
        $this->middleware(PermissionMiddleware::class . ':edit signed-documents|administrator')->only(['update']);
        $this->middleware(PermissionMiddleware::class . ':delete signed-documents|administrator')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $limit = $request->has('limit') ? $request->limit : 10;

            $query = Document::with([
                        'supplier' => function ($q) {
                            $q->withTrashed()->with(['user' => fn($u) => $u->withTrashed()]);
                        },
                        'token',
                        'tokens',
                        'user'
                    ])
                    ->applyFilters(
                        $request->only([
                            'search',
                            'orderByField',
                            'orderBy',
                            'supplier_id',
                            'status'
                        ])
                    );

            $count = $query->count();

            $documents = ($limit == -1) ? $query->paginate($query->count()) : $query->paginate($limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'documents' => $documents,
                    'documentsTotalCount' => $count,
                    'suppliers' => Supplier::with(['user.userDetail', 'billings'])->whereNull('boss_id')->get()
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
    public function store(DocumentRequest $request): JsonResponse
    {

        try {

            $document = Document::createDocument($request);

            $order_id = Document::where('supplier_id', $document->supplier_id)
                            ->latest('order_id')
                            ->first()
                            ->order_id ?? 0;

            $document->order_id = $order_id + 1;
            $document->update();

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $path = 'documents/';
                
                $file_data = uploadFileWithOriginalName($file, $path);
                $document->file = $file_data['filePath'];
                $document->save();
            }

            // Create initial token with 'created' status when document is created
            $signingToken = Str::uuid()->toString();
            $token = $document->tokens()->create([
                'signing_token' => $signingToken,
                'recipient_email' => null, // Will be set when signature is requested
                'token_expires_at' => now()->addDays(30),
                'signature_status' => 'created',
                'placement_x' => 0,
                'placement_y' => 0,
                'placement_page' => 1, // Default to page 1
                'signature_alignment' => 'left',
            ]);

            // Log 'created' event when document is created
            \App\Models\TokenHistory::logEvent(
                tokenId: $token->id,
                eventType: \App\Models\TokenHistory::EVENT_CREATED,
                description: 'Dokument skapat i systemet',
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
                metadata: [
                    'document_id' => $document->id,
                    'document_title' => $document->title,
                    'user_id' => $document->user_id,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Dokumentet har skapats',
                'data' => ['document' => $document->load('tokens')]
            ]);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error '.$ex->getMessage(),
                'exception' => $ex->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id): JsonResponse
    {
        try {
            $document = Document::with(['tokens.history' => function($query) {
                $query->orderBy('created_at', 'asc');
            }])->find($id);

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Dokumentet hittades inte'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $document
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
    public function destroy($id): JsonResponse
    {
        try {

            $document = Document::find($id);

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Dokumentet hittades inte'
                ], 404);
            }

            Document::deleteDocument($id);

            return response()->json([
                'success' => true,
                'message' => 'Dokumentet har raderats'
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
     * Send document(s) via email
     */
    public function send(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'ids' => 'required',
                'email' => 'required|email',
            ]);

            $result = Document::sendDocument($request);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Dokumentet har skickats via e-post'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Kunde inte skicka dokumentet'
            ], 400);

        } catch (\Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Ett fel inträffade: ' . $ex->getMessage()
            ], 500);
        }
    }

    /**
     * Get PDF for admin preview
     */
    public function getAdminPreviewPdf(Document $document)
    {
        if ($document && $document->file && Storage::disk('public')->exists($document->file)) {
            $path = storage_path('app/public/' . $document->file);
            return response()->file($path);
        }

        abort(404, 'PDF-fil hittades inte för detta dokument.');
    }

    /**
     * Send signature request for a document
     */
    public function sendSignatureRequest(Document $document, Request $request)
    {   
        $validated = $request->validate([
            'email' => 'required|email',
            'x' => 'required|numeric',
            'y' => 'required|numeric',
            'page' => 'required|integer'
        ]);

        if (!$document->file) {
            return response()->json([
                'message' => 'Det finns ännu ingen PDF-fil att underteckna för detta dokument.'
            ], 422);
        }

        // Get or create token for this document
        // First check for tokens with 'created' or 'delivery_issues' status
        $token = $document->tokens()
            ->whereIn('signature_status', ['created', 'delivery_issues'])
            ->latest()
            ->first();
        
        if (!$token) {
            // If no 'created' or 'delivery_issues' token exists, create a new one
            $signingToken = Str::uuid()->toString();
            $token = $document->tokens()->create([
                'signing_token' => $signingToken,
                'recipient_email' => $validated['email'],
                'token_expires_at' => now()->addDays(7),
                'signature_status' => 'created',
                'placement_x' => $validated['x'],
                'placement_y' => $validated['y'],
                'placement_page' => $validated['page'],
                'signature_alignment' => $request->get('alignment', 'left'),
            ]);

            // Log 'created' event for new token
            \App\Models\TokenHistory::logEvent(
                tokenId: $token->id,
                eventType: \App\Models\TokenHistory::EVENT_CREATED,
                description: 'Dokument skapat i systemet',
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
                metadata: [
                    'document_id' => $document->id,
                    'document_title' => $document->title,
                    'recipient' => $validated['email'],
                ]
            );
        } else {
            // Update existing token with signature details
            $token->update([
                'recipient_email' => $validated['email'],
                'token_expires_at' => now()->addDays(7),
                'placement_x' => $validated['x'],
                'placement_y' => $validated['y'],
                'placement_page' => $validated['page'],
                'signature_alignment' => $request->get('alignment', 'left'),
            ]);
        }

        // Send email with error handling
        try {
            // Update status to 'sent'
            $token->update(['signature_status' => 'sent']);
            
            // Log 'sent' event
            \App\Models\TokenHistory::logEvent(
                tokenId: $token->id,
                eventType: \App\Models\TokenHistory::EVENT_SENT,
                description: 'E-post skickad till ' . $validated['email'],
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
                metadata: ['recipient' => $validated['email']]
            );

            $document->description = $request->text === 'null' ? null : $request->text;
            $document->save();

            $signingUrl = env('APP_DOMAIN') . '/sign/' . $token->signing_token;
            $clientEmail = $validated['email'];
            $subject = 'Solicitud para firmar su documento';
            $data = [
                'signingUrl' => $signingUrl,
                'text' => $request->text === 'null' ? null : $request->text
            ];

            \Mail::send(
                'emails.documents.signature_request'
                , $data
                , function ($message) use ($clientEmail, $subject) {
                    $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
                    $message->to($clientEmail)->subject($subject);
            });

            $token->update(['signature_status' => 'delivered']);
            
            // Log 'delivered' event
            \App\Models\TokenHistory::logEvent(
                tokenId: $token->id,
                eventType: \App\Models\TokenHistory::EVENT_DELIVERED,
                description: 'E-post för underskriftsförfrågan levererad framgångsrikt',
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
                metadata: ['recipient' => $validated['email']]
            );
            
            return response()->json([
                'message' => 'Begäran om underskrift skickad med framgång.'
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error sending signature email for document: ' . $e->getMessage(), [
                'exception' => $e,
                'document_id' => $document->id,
                'email' => $validated['email']
            ]);
            
            $token->update(['signature_status' => 'delivery_issues']);
            
            // Log 'delivery_issues' event
            \App\Models\TokenHistory::logEvent(
                tokenId: $token->id,
                eventType: \App\Models\TokenHistory::EVENT_DELIVERY_ISSUES,
                description: 'Fel vid sändning av e-post',
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
                metadata: [
                    'error' => $e->getMessage(),
                    'error_type' => get_class($e),
                    'recipient' => $validated['email']
                ]
            );
            
            return response()->json([
                'message' => 'Det gick inte att skicka e-postmeddelandet. Kontrollera e-postadressen och försök igen.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resend the signature request using the last "sent" token (same URL and coordinates)
     */
    public function resendSignatureRequest(Document $document, Request $request)
    {
        // Find the most recent token with allowed statuses for this document
        $token = $document->tokens()
            ->whereIn('signature_status', ['sent', 'delivered', 'reviewed', 'delivery_issues'])
            ->latest()
            ->first();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Det finns ingen aktiv underskriftsförfrågan att skicka om.'
            ], 422);
        }

        // Re-send the original email to the same recipient with the SAME token/url
        try {
            // Update status to 'sent' before attempting to resend
            $token->update(['signature_status' => 'sent']);
            
            // Register 'sent' event for the resend
            \App\Models\TokenHistory::logEvent(
                tokenId: $token->id,
                eventType: \App\Models\TokenHistory::EVENT_SENT,
                description: 'Vidarebefordran av signaturpost påbörjad',
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
                metadata: ['recipient' => $token->recipient_email, 'resend' => true]
            );
            
            $signingUrl = env('APP_DOMAIN') . '/sign/' . $token->signing_token;
            $clientEmail = $token->recipient_email;
            $subject = 'Solicitud para firmar su documento';
            $data = [
                'signingUrl' => $signingUrl,
                'text' => $document->description
            ];

            \Mail::send(
                'emails.documents.signature_request'
                , $data
                , function ($message) use ($clientEmail, $subject) {
                    $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
                    $message->to($clientEmail)->subject($subject);
            });
            
            // Update status to delivered if the resend was successful
            $token->update(['signature_status' => 'delivered']);
            
            // Register 'delivered' event for the resend
            \App\Models\TokenHistory::logEvent(
                tokenId: $token->id,
                eventType: \App\Models\TokenHistory::EVENT_DELIVERED,
                description: 'E-post vidarebefordrad',
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
                metadata: ['recipient' => $token->recipient_email, 'resend' => true]
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Återutsändningsmeddelandet har vidarebefordrats.'
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error resending signature email: ' . $e->getMessage(), [
                'exception' => $e,
                'document_id' => $document->id,
                'token_id' => $token->id,
                'email' => $token->recipient_email
            ]);
            
            // If resend fails, change to delivery_issues
            $token->update(['signature_status' => 'delivery_issues']);
            
            // Register 'delivery_issues' event for the failed resend
            \App\Models\TokenHistory::logEvent(
                tokenId: $token->id,
                eventType: \App\Models\TokenHistory::EVENT_DELIVERY_ISSUES,
                description: 'Fel vid vidarebefordran av e-post',
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
                metadata: [
                    'error' => $e->getMessage(),
                    'error_type' => get_class($e),
                    'recipient' => $token->recipient_email,
                    'resend' => true
                ]
            );
            
            return response()->json([
                'success' => false,
                'message' => 'Det gick inte att skicka om e-postmeddelandet: ' . $e->getMessage()
            ], 500);
        }
    }
}

