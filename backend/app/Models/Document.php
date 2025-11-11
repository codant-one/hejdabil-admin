<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'file',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**** Relationships ****/
    public function tokens()
    {
        return $this->hasMany(Token::class, 'document_id');
    }

    /**** Public methods ****/
    public static function createDocument($request)
    {
        $document = self::create([
            'title' => $request->title ?? 'Untitled Document',
            'description' => $request->description ?? null,
        ]);

        return $document;
    }

    public static function deleteDocument($id)
    {
        $document = self::find($id);
        if ($document) {
            if ($document->file) {
                \Storage::disk('public')->delete($document->file);
            }
            $document->delete();
        }
    }
}

