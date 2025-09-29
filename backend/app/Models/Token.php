<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Token extends Model
{
    use HasFactory;

    protected $fillable = [
        'agreement_id',
        'signature_status',
        'signing_token',
        'token_expires_at',
        'signed_at',
        'signature_image_path',
        'signed_pdf_path',
    ];


    protected $casts = [
        'token_expires_at' => 'datetime',
        'signed_at' => 'datetime',
    ];

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }
}
