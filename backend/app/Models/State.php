<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class State extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get the state id by its label (ids depend on the StateSeeder order).
     */
    public static function idByLabel(string $label): int
    {
        $id = static::query()->where('label', $label)->value('id');

        if ($id === null) {
            throw new \RuntimeException("State with label '{$label}' not found. Run: php artisan db:seed --class=StateSeeder");
        }

        return (int) $id;
    }
}
