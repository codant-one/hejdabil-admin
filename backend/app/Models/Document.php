<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [ 
        'user_id',
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

    public function user(){
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**** Public methods ****/
    public static function createDocument($request)
    {
        $document = self::create([
            'user_id' => Auth::user()->id,
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

