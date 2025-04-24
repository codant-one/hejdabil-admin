<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class VerifyJwt
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (Exception $e) {

            if($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException){
                return Response()->json([
                    'success' => false,
                    'feedback' => 'invalid_token',
                    'message' => 'Ogiltig token'
                ], 401);
            }
            
            if($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException){
                return Response()->json([
                    'success' => false,
                    'feedback' => 'expired_token',
                    'message' => 'Utgången Token'
                ], 401);
            }

            return Response()->json([
                'success' => false,
                'feedback' => $e,
                'message' => 'Token för fel'
            ], 401);

        }

        return $next($request);
    }
}
