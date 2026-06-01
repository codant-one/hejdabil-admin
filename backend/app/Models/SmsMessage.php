<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class SmsMessage extends Model
{
    use HasFactory;

    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_FAILED = 'failed';

    protected $guarded = [];

    protected $casts = [
        'billing_month' => 'date',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id')->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id')->withTrashed();
    }

    public function scopeForBillingMonth($query, Carbon|string $billingMonth)
    {
        $month = $billingMonth instanceof Carbon
            ? $billingMonth->copy()->startOfMonth()->toDateString()
            : Carbon::parse($billingMonth)->startOfMonth()->toDateString();

        return $query->whereDate('billing_month', $month);
    }

    public static function resolveBillingMonth(Carbon|string|null $billingMonth = null): string
    {
        if ($billingMonth instanceof Carbon) {
            return $billingMonth->copy()->startOfMonth()->toDateString();
        }

        if (is_string($billingMonth) && trim($billingMonth) !== '') {
            return Carbon::parse($billingMonth)->startOfMonth()->toDateString();
        }

        return now()->startOfMonth()->toDateString();
    }
}