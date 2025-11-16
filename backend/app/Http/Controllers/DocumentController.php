<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class DocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Document::with(['tokens', 'user']);

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Ordering
        $orderByField = $request->get('orderByField', 'created_at');
        $orderBy = $request->get('orderBy', 'desc');
        $query->orderBy($orderByField, $orderBy);

        // Pagination
        $limit = $request->get('limit', 10);
        $page = $request->get('page', 1);
        
        $documents = $query->paginate($limit, ['*'], 'page', $page);
        $totalCount = Document::count();

        return response()->json([
            'success' => true,
            'data' => [
                'documents' => $documents,
                'documentsTotalCount' => $totalCount,
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf|max:10240', // 10MB max
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

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
                'message' => 'Document created successfully',
                'data' => ['document' => $document->load('tokens')]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating document: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id): JsonResponse
    {
        $document = Document::with(['tokens'])->find($id);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => ['document' => $document]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $document = Document::find($id);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'file' => 'sometimes|file|mimes:pdf|max:10240',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            if ($request->has('title')) {
                $document->title = $request->title;
            }
            if ($request->has('description')) {
                $document->description = $request->description;
            }

            if ($request->hasFile('file')) {
                // Delete old file
                if ($document->file) {
                    Storage::disk('public')->delete($document->file);
                }

                $file = $request->file('file');
                $path = 'documents/';
                
                $file_data = uploadFileWithOriginalName($file, $path);
                $document->file = $file_data['filePath'];
            }

            $document->save();

            return response()->json([
                'success' => true,
                'message' => 'Document updated successfully',
                'data' => ['document' => $document->load('tokens')]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating document: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id): JsonResponse
    {
        $document = Document::find($id);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found'
            ], 404);
        }

        try {
            Document::deleteDocument($id);
            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting document: ' . $e->getMessage()
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

        abort(404, 'PDF file not found for this document.');
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
            return response()->json(['message' => 'Este documento aún no tiene un PDF cargado para firmar.'], 422);
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
        
        return response()->json(['message' => 'Solicitud de firma enviada con éxito.']);
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
            return response()->json(['message' => 'Este documento aún no tiene un PDF cargado para firmar.'], 422);
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
        
        return response()->json(['message' => 'Solicitud de firma enviada con éxito.']);
    }
}

