<?php

namespace App\Events;

use App\Models\TokenHistory;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TokenHistoryUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $tokenHistory;
    public $documentId;
    public $agreementId;

    /**
     * Create a new event instance.
     */
    public function __construct(TokenHistory $tokenHistory)
    {
        $this->tokenHistory = $tokenHistory;
        
        // Load token relationship to get document/agreement ID
        $token = $tokenHistory->token;
        $this->documentId = $token->signed_document_id ?? null;
        $this->agreementId = $token->agreement_id ?? null;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel
    {
        return new Channel('token-history');
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'history.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'event_type' => $this->tokenHistory->event_type,
            'token_id' => $this->tokenHistory->token_id,
            'document_id' => $this->documentId,
            'agreement_id' => $this->agreementId,
            'description' => $this->tokenHistory->description,
            'timestamp' => $this->tokenHistory->created_at->toISOString(),
        ];
    }
}
