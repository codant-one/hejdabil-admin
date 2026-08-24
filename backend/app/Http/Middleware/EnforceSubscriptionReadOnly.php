<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceSubscriptionReadOnly
{
    private const INACTIVE_SUBSCRIPTION_MESSAGE = 'Din prenumeration har löpt ut. Du har endast läsbehörighet.';

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return $next($request);
        }

        if ($this->isReadOnlyRequest($request)) {
            return $next($request);
        }

        if (!$this->isSupplierContext($user)) {
            return $next($request);
        }

        $supplier = $this->resolveSupplierForUser($user);

        if (!$supplier) {
            return $next($request);
        }

        if ((int) $supplier->is_subscription_active === 0) {
            return response()->json([
                'success' => false,
                'feedback' => 'subscription_inactive',
                'message' => 'subscription_inactive',
                'errors' => self::INACTIVE_SUBSCRIPTION_MESSAGE,
            ], 403);
        }

        return $next($request);
    }

    private function isReadOnlyRequest(Request $request): bool
    {
        return in_array(strtoupper($request->method()), ['GET', 'HEAD', 'OPTIONS'], true);
    }

    private function isSupplierContext($user): bool
    {
        return $user->hasRole('Supplier') || $user->hasRole('User');
    }

    private function resolveSupplierForUser($user)
    {
        if ($user->hasRole('User')) {
            $user->loadMissing('supplier.boss');

            return $user->supplier?->boss;
        }

        $user->loadMissing('supplier');

        return $user->supplier;
    }
}
