<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TokenHistory extends Model
{
    use HasFactory;

    protected $table = 'token_history';

    protected $fillable = [
        'token_id',
        'event_type',
        'description',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**** Relationship ****/
    public function token(): BelongsTo
    {
        return $this->belongsTo(Token::class);
    }

    /**
     * Constants for event types
     */
    const EVENT_CREATED = 'created';
    const EVENT_SENT = 'sent';
    const EVENT_DELIVERED = 'delivered';
    const EVENT_DELIVERY_ISSUES = 'delivery_issues';
    const EVENT_REVIEWED = 'reviewed';
    const EVENT_SIGNED = 'signed';
    const EVENT_FAILED = 'failed';

    /**
     * Helper to create a history event
     */
    public static function logEvent(
        int $tokenId, 
        string $eventType, 
        ?string $description = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'token_id' => $tokenId,
            'event_type' => $eventType,
            'description' => $description,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'metadata' => $metadata,
        ]);
    }
}
