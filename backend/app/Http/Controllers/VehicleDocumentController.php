<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use App\Models\VehicleDocument;

class VehicleDocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {

            $user = Auth::user();
            $name = $user->name . ' ' . $user->last_name;
            $image = $request->file('file');
            $originalName = $image->getClientOriginalName();

            $path = 'vehicles/' . Str::slug($name) . '/';
            $fullFilePath = $path . $originalName;

            $existsInDatabase = VehicleDocument::where('file', $fullFilePath)->exists();
            $existsInStorage = Storage::disk('public')->exists($fullFilePath);

            if ($existsInDatabase || $existsInStorage) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dokumentet är redan sparat i systemet för ' . $name
                ]);
            }

            $document = VehicleDocument::createDocument($request);

            if ($request->hasFile('file')) {
                $document = VehicleDocument::with(['user'])->find($document->id);
                $name = $document->user->name . ' ' . $document->user->last_name;
                $image = $request->file('file');

                $path = 'vehicles/' . Str::slug($name) . '/';

                $file_data = uploadFileWithOriginalName($image, $path);

                $document->file = $file_data['filePath'];
                $document->update();
            } 

            return response()->json([
                'success' => true
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
    public function show($id)
    {
        try {

            $document = VehicleDocument::find($id);

            if (!$document)
                return response()->json([
                    'sucess' => false,
                    'feedback' => 'not_found',
                    'message' => 'Dokument hittades inte'
                ], 404);

            return response()->json([
                'success' => true
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
    public function destroy($id)
    {
        try {

            $document = VehicleDocument::find($id);
        
            if (!$document)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Dokument hittades inte'
                ], 404);
            
            $document->deleteDocument($id);

            return response()->json([
                'success' => true
            ], 200);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error',
                'exception' => $ex->getMessage()
            ], 500);
        }
    }

    public function send(Request $request)
    {
        try {

            VehicleDocument::sendDocument($request);

            return response()->json([
                'success' => true
            ], 200);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error',
                'exception' => $ex->getMessage()
            ], 500);
        }
    }

}
