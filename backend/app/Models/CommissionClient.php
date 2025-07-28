<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionClient extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function commission(): BelongsTo
    {
        return $this->belongsTo(Commission::class, 'commision_id');
    }

    public function client_type(): BelongsTo
    {
        return $this->belongsTo(ClientType::class, 'client_type_id');
    }

    public function identification(): BelongsTo
    {
        return $this->belongsTo(Identification::class, 'identification_id');
    }
}