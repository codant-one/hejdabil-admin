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
    const EVENT_CANCELLED = 'cancelled';
    const EVENT_FAILED = 'failed';

    const CHANNEL_EMAIL = 'email';
    const CHANNEL_SMS = 'sms';

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
            'user_agent' => $userAgent ? mb_substr($userAgent, 0, 1000) : null,
            'metadata' => $metadata,
        ]);
    }

    public static function buildChannelMetadata(string $channel, ?string $recipient = null, array $extra = []): array
    {
        $metadata = array_merge($extra, [
            'channel' => $channel,
        ]);

        if ($channel === self::CHANNEL_SMS) {
            $metadata['phone'] = $recipient;
            $metadata['recipient_phone'] = $recipient;
        } else {
            $metadata['email'] = $recipient;
            $metadata['recipient'] = $recipient;
        }

        return array_filter($metadata, static fn ($value) => $value !== null && $value !== '');
    }

    public static function buildChannelSentDescription(string $channel, ?string $recipient = null, bool $resend = false): string
    {
        $channelLabel = $channel === self::CHANNEL_SMS ? 'SMS' : 'e-post';
        $recipientText = $recipient ?: 'mottagare';

        return $resend
            ? 'Signeringsförfrågan skickad igen via ' . $channelLabel . ' till ' . $recipientText
            : 'Signeringsförfrågan skickad via ' . $channelLabel . ' till ' . $recipientText;
    }

    public static function buildChannelDeliveredDescription(string $channel, ?string $recipient = null, bool $resend = false): string
    {
        $recipientText = $recipient ?: 'mottagare';

        if ($channel === self::CHANNEL_SMS) {
            return $resend
                ? 'SMS levererat igen till ' . $recipientText
                : 'SMS levererat till ' . $recipientText;
        }

        return $resend
            ? 'E-post levererad igen till ' . $recipientText
            : 'E-post levererad till ' . $recipientText;
    }

    public static function buildChannelFailureDescription(string $channel, ?string $recipient = null, bool $resend = false): string
    {
        $recipientText = $recipient ?: 'mottagare';

        if ($channel === self::CHANNEL_SMS) {
            return $resend
                ? 'Fel vid skicka om SMS till ' . $recipientText
                : 'Fel vid sändning av SMS till ' . $recipientText;
        }

        return $resend
            ? 'Fel vid skicka om e-post till ' . $recipientText
            : 'Fel vid sändning av e-post till ' . $recipientText;
    }
}
