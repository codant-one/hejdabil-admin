<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserInactivity
{
    private const DEFAULT_AUTH_LOGOUT_MESSAGE = 'Din session har avslutats. Logga in igen för att fortsätta.';
    private const INACTIVE_USER_MESSAGE = 'Denna användare har inaktiverats. Kontakta administratören.';

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'feedback' => 'unauthenticated',
                'message' => self::DEFAULT_AUTH_LOGOUT_MESSAGE,
            ], 401);
        }

        if (method_exists($user, 'trashed') && $user->trashed()) {
            return response()->json([
                'success' => false,
                'feedback' => 'user_inactive',
                'message' => 'user_inactive',
                'errors' => self::INACTIVE_USER_MESSAGE,
            ], 403);
        }

        $idleTimeoutMinutes = max((int) env('IDLE_TIMEOUT_MINUTES', 60), 1);
        $now = Carbon::now();
        $lastActivity = $user->online ? Carbon::parse($user->online) : null;

        if ($lastActivity && $lastActivity->addMinutes($idleTimeoutMinutes)->lte($now)) {
            try {
                Auth::guard('api')->logout();
            } catch (\Throwable $exception) {
                // Ignore logout failures and still terminate the request as inactive.
            }

            return response()->json([
                'success' => false,
                'feedback' => 'inactive_session',
                'message' => self::DEFAULT_AUTH_LOGOUT_MESSAGE,
            ], 401);
        }

        $user->forceFill(['online' => $now])->saveQuietly();

        return $next($request);
    }
}
