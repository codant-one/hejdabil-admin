<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function resolveSmsSenderForCurrentUser(): ?string
    {
        $user = Auth::user();

        if (!$user) {
            return null;
        }

        if ($user->hasAnyRole(['SuperAdmin', 'Administrator'])) {
            return null;
        }

        $user->loadMissing('supplier.boss');

        if ($user->hasRole('Supplier')) {
            return $user->supplier?->sms_sender ?: null;
        }

        if ($user->hasRole('User')) {
            return $user->supplier?->boss?->sms_sender ?: null;
        }

        return null;
    }
}
