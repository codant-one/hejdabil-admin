<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Token extends Model
{
    use HasFactory;

    protected $fillable = [
        'agreement_id',
        'document_id',
        'signable_type',
        'signable_id',
        'signature_status',
        'recipient_email',
        'signing_token',
        'token_expires_at',
        'signed_at',
        'viewed_at',
        'signature_image_path',
        'signed_pdf_path',
        'placement_x', 
        'placement_y', 
        'placement_page',
        'signature_alignment',
    ];


    protected $casts = [
        'token_expires_at' => 'datetime',
        'signed_at' => 'datetime',
        'viewed_at' => 'datetime',
    ];

    /**** Relationship ****/
    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function signable(): MorphTo
    {
        return $this->morphTo();
    }

    public function histories(): HasMany
    {
        return $this->hasMany(TokenHistory::class);
    }
}
