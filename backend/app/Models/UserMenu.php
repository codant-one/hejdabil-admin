<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMenu extends Model
{
    use HasFactory;

    protected $fillable = [
        'menus',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
