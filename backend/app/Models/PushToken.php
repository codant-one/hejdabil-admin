<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'platform',
        'device_id',
        'session_active',
        'notifications_enabled',
        'last_used_at',
    ];

    protected $casts = [
        'session_active' => 'boolean',
        'notifications_enabled' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    public function setTokenAttribute(string $token): void
    {
        $this->attributes['token'] = $token;
        $this->attributes['token_hash'] = hash('sha256', $token);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
