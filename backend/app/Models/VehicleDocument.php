<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class VehicleDocument extends Model
{
    use HasFactory;

    protected $guarded = [];
    
    /**** Relationship ****/
    public function vehicle(){
        return $this->belongsTo(Vehicle::class, 'vehicle_id', 'id');
    }

    public function user(){
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function type(){
        return $this->belongsTo(DocumentType::class, 'document_type_id', 'id');
    }

    /**** Public methods ****/
    public static function createDocument($request) {

        $document = self::create([
            'user_id' => Auth::user()->id,
            'vehicle_id' => $request->vehicle_id,
            'document_type_id' => $request->document_type_id,
            'reference' => $request->reference
        ]);
        
        return $document;
    }

    public static function deleteDocument($id) {
        self::deleteDocuments(array($id));
    }

    public static function deleteDocuments($ids) {
        foreach ($ids as $id) {
            $document = self::find($id);
            $document->delete();

            if($document->file)
                deleteFile($document->file);
        }
    }

    public static function sendDocument($request) {

        $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
        $documents = self::with('vehicle')->whereIn('id', $ids)->get();

        if ($documents->isEmpty()) {
            return false;
        }

        $regNums = $documents->pluck('vehicle.reg_num')->unique()->implode(', ');
        $data = ['reg_num' => $regNums ];

        $subject = $regNums . ' Dokuments';

        try {
            \Mail::send(
                'emails.documents.vehicles'
                , $data
                , function ($message) use ($request, $documents, $subject) {
                $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
                $message->to($request->email)->subject($subject);

                foreach ($documents as $document) {
                    $pathToFile = storage_path('app/public/' . $document->file);

                    if (file_exists($pathToFile)) {
                        $mime = mime_content_type($pathToFile);
                        $message->attach($pathToFile, [
                            'as' => Str::of($document->file)->afterLast('/'),
                            'mime' => $mime
                        ]);
                    }
                }
            });

            return true;

        } catch (\Exception $e) {
            Log::error('Error al enviar correo:', ['error' => $e->getMessage()]);
            return false;
        }
        
    }
}
