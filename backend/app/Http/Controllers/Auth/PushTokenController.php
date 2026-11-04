<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PushToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PushTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        if (! $user = Auth::user()) {
            return $this->unauthorizedResponse();
        }

        $validator = Validator::make($request->all(), [
            'token' => 'required|string|min:10',
            'platform' => 'required|in:android,ios',
            'device_id' => 'required|string|max:191',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'validation_error',
                'data' => $validator->errors(),
            ], 422);
        }

        $token = $request->string('token')->toString();
        $tokenHash = hash('sha256', $token);

        $pushToken = DB::transaction(function () use ($user, $request, $token, $tokenHash) {
            $existing = PushToken::query()
                ->where('token_hash', $tokenHash)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $existing->fill([
                    'user_id' => $user->id,
                    'token' => $token,
                    'platform' => $request->input('platform'),
                    'device_id' => $request->input('device_id'),
                    'session_active' => true,
                    'last_used_at' => now(),
                ]);
                $existing->save();

                return $existing;
            }

            return PushToken::create([
                'user_id' => $user->id,
                'token' => $token,
                'platform' => $request->input('platform'),
                'device_id' => $request->input('device_id'),
                'session_active' => true,
                'notifications_enabled' => true,
                'last_used_at' => now(),
            ]);
        });

        return response()->json([
            'message' => 'push_token_saved',
            'data' => [
                'id' => $pushToken->id,
                'device_id' => $pushToken->device_id,
                'platform' => $pushToken->platform,
                'session_active' => (bool) $pushToken->session_active,
                'notifications_enabled' => (bool) $pushToken->notifications_enabled,
            ],
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        if (! $user = Auth::user()) {
            return $this->unauthorizedResponse();
        }

        $validator = Validator::make($request->all(), [
            'device_id' => 'nullable|string|max:191|required_without:token',
            'token' => 'nullable|string|min:10|required_without:device_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'validation_error',
                'data' => $validator->errors(),
            ], 422);
        }

        $query = $user->pushTokens();

        if ($request->filled('device_id')) {
            $query->where('device_id', $request->input('device_id'));
        } else {
            $query->where('token_hash', hash('sha256', $request->string('token')->toString()));
        }

        $deleted = $query->delete();

        return response()->json([
            'message' => 'push_token_deleted',
            'data' => ['deleted' => $deleted],
        ]);
    }

    public function deactivateSession(Request $request): JsonResponse
    {
        if (! $user = Auth::user()) {
            return $this->unauthorizedResponse();
        }

        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|max:191',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'validation_error',
                'data' => $validator->errors(),
            ], 422);
        }

        $updated = $user->pushTokens()
            ->where('device_id', $request->input('device_id'))
            ->update([
                'session_active' => false,
                'last_used_at' => now(),
            ]);

        return response()->json([
            'message' => 'push_token_session_deactivated',
            'data' => ['updated' => $updated],
        ]);
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        if (! $user = Auth::user()) {
            return $this->unauthorizedResponse();
        }

        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|max:191',
            'notifications_enabled' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'validation_error',
                'data' => $validator->errors(),
            ], 422);
        }

        $updated = $user->pushTokens()
            ->where('device_id', $request->input('device_id'))
            ->update([
                'notifications_enabled' => (bool) $request->input('notifications_enabled'),
                'last_used_at' => now(),
            ]);

        return response()->json([
            'message' => 'push_token_preferences_updated',
            'data' => ['updated' => $updated],
        ]);
    }

    private function unauthorizedResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'invalid_credentials',
            'data' => null,
        ], 401);
    }
}
