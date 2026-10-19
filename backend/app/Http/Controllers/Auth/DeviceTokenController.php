<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

use App\Services\LoginResponseService;

use App\Models\DeviceToken;

class DeviceTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        if (! $user = Auth::user()) {
            return $this->invalidToken();
        }

        $validator = Validator::make($request->all(), [
            'device_name' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'validation_error',
                'data' => $validator->errors(),
            ], 422);
        }

        $deviceName = $request->input('device_name');

        // Purge this user's dead tokens and replace the active one of the same device.
        $user->deviceTokens()
            ->where(function ($query) {
                $query->whereNotNull('revoked_at')->orWhere('expires_at', '<=', now());
            })
            ->delete();

        if ($deviceName) {
            $user->deviceTokens()->where('device_name', $deviceName)->delete();
        }

        $token = Str::random(64);
        $deviceToken = $user->deviceTokens()->create([
            'token' => $token,
            'device_name' => $deviceName,
            'expires_at' => now()->addDays(90),
        ]);

        return response()->json([
            'message' => 'device_token_created',
            'data' => [
                'token' => $token,
                'expires_at' => $deviceToken->expires_at,
            ],
        ], 201);
    }

    public function login(Request $request, LoginResponseService $loginResponse): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string|size:64',
        ]);

        if ($validator->fails()) {
            return $this->invalidToken();
        }

        $deviceToken = DeviceToken::with(['user' => function ($query) {
            $query->withTrashed();
        }])->where('token_hash', hash('sha256', $request->input('token')))->first();

        if (! $deviceToken || $deviceToken->revoked_at !== null ||
            $deviceToken->expires_at === null || $deviceToken->expires_at->lessThanOrEqualTo(now())) {
            return $this->invalidToken();
        }

        // Definitive account deletion physically removes the user and its device tokens.
        $user = $deviceToken->user;
        if (! $user) {
            return $this->invalidToken();
        }

        if ($user->isInactive()) {
            return response()->json([
                'message' => 'user_inactive',
                'data' => null,
            ], 403);
        }

        $token = Auth::login($user);
        $response = $loginResponse->respond(
            $user,
            $token,
            (bool) config('auth.device_login_bypasses_2fa'),
            false
        );

        $deviceToken->update(['last_used_at' => now()]);

        return $response;
    }

    public function destroy(): JsonResponse
    {
        if (! $user = Auth::user()) {
            return $this->invalidToken();
        }

        $user->deviceTokens()->whereNull('revoked_at')->update([
            'revoked_at' => now(),
        ]);

        return response()->json([
            'message' => 'device_tokens_revoked',
            'data' => null,
        ], 200);
    }

    private function invalidToken(): JsonResponse
    {
        return response()->json([
            'message' => 'invalid_credentials',
            'data' => null,
        ], 401);
    }
}
