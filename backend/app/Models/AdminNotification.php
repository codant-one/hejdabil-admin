<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminNotification extends Model
{
    public const AUDIENCE_ALL = 'all';
    public const AUDIENCE_ROLES = 'roles';
    public const AUDIENCE_USERS = 'users';

    // states.label values (StateSeeder)
    public const STATE_QUEUED = 'queued';
    public const STATE_SENDING = 'sending';
    public const STATE_SENT = 'sent';
    public const STATE_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'state_id',
        'title',
        'subtitle',
        'route',
        'audience',
        'roles',
        'user_ids',
        'recipients_count',
        'sent_at',
    ];

    protected $casts = [
        'roles' => 'array',
        'user_ids' => 'array',
        'recipients_count' => 'integer',
        'state_id' => 'integer',
        'sent_at' => 'datetime',
    ];

    public static function stateId(string $label): int
    {
        return State::idByLabel($label);
    }

    public function hasState(string $label): bool
    {
        return $this->state_id === self::stateId($label);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    /** Administrator who created the broadcast. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id')->withTrashed();
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'broadcast_id');
    }
}
