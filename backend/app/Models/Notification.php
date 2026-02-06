<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**** Relationship ****/
    public function user() {
        return $this->belongsTo(User::class);
    }

    /**** Scopes ****/
    public function markAsRead() {
        $this->update(['read' => true]);
    }

    public function scopeUnread($query) {
        return $query->where('read', false);
    }

    public function scopeForUser($query, $userId) {
        return $query->where('user_id', $userId);
    }
}
