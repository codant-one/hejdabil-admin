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
                        'tokens',
                        'user'
                    ])
                    ->applyFilters(
                        $request->only([
                            'search',
                            'orderByField',
                            'orderBy',
                            'supplier_id'
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

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $path = 'documents/';
                
                $file_data = uploadFileWithOriginalName($file, $path);
                $document->file = $file_data['filePath'];
                $document->save();
            }

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
            $document = Document::with(['tokens'])->find($id);

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Dokumentet hittades inte'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'document' => $document
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
            'page' => 'required|integer',
        ]);

        if (!$document->file) {
            return response()->json([
                'message' => 'Det finns ännu ingen PDF-fil att underteckna för detta dokument.'
            ], 422);
        }

        $signingToken = Str::uuid()->toString();
        
        $token = $document->tokens()->create([
            'signing_token' => $signingToken,
            'recipient_email' => $validated['email'],
            'token_expires_at' => now()->addDays(7),
            'signature_status' => 'sent',
            'placement_x' => $validated['x'],
            'placement_y' => $validated['y'],
            'placement_page' => $validated['page'],
            'signature_alignment' => $request->get('alignment', 'left'),
        ]);

        // Send email
        \Mail::to($validated['email'])->send(new \App\Mail\SignatureRequestMail($token));
        
        return response()->json([
            'message' => 'Begäran om underskrift skickad med framgång.'
        ]);
    }

    /**
     * Send static signature request (without coordinates)
     */
    public function sendStaticSignatureRequest(Document $document, Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        if (!$document->file) {
            return response()->json([
                'message' => 'Det finns ännu ingen PDF-fil att underteckna för detta dokument.'
            ], 422);
        }

        $signingToken = Str::uuid()->toString();
        
        $token = $document->tokens()->create([
            'signing_token' => $signingToken,
            'recipient_email' => $validated['email'],
            'token_expires_at' => now()->addDays(7),
            'signature_status' => 'sent',
            'placement_x' => null,
            'placement_y' => null,
            'placement_page' => 1,
            'signature_alignment' => $request->get('alignment', 'left'),
        ]);

        \Mail::to($validated['email'])->send(new \App\Mail\SignatureRequestMail($token));
        
        return response()->json([
            'message' => 'Begäran om underskrift skickad med framgång.'
        ]);
    }

    /**
     * Resend the signature request using the last "sent" token (same URL and coordinates)
     */
    public function resendSignatureRequest(Document $document, Request $request)
    {
        // Find the most recent token with status 'sent' for this document
        $token = $document->tokens()->where('signature_status', 'sent')->latest()->first();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Det finns ingen aktiv underskriftsförfrågan att skicka om.'
            ], 422);
        }

        // Re-send the original email to the same recipient with the SAME token/url
        try {
            \Mail::to($token->recipient_email)->send(new \App\Mail\SignatureRequestMail($token));
            return response()->json([
                'success' => true,
                'message' => 'Återutsändningsmeddelandet har vidarebefordrats.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Det gick inte att skicka om e-postmeddelandet: ' . $e->getMessage()
            ], 500);
        }
    }
}

