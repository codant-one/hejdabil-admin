<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\JsonResponse;

class LoginResponseService
{
    public function respond(User $user, string $token, bool $bypassTwoFactor = false, bool $allowDebugBypass = true): JsonResponse
    {
        $user->online = now();
        $user->save();

        if ($bypassTwoFactor || ($allowDebugBypass && env('APP_DEBUG')) || $user->is_2fa === 0) {
            return response()->json([
                'success' => true,
                'message' => 'login_success',
                'data' => $this->tokenData($user, $token),
            ], 200);
        }

        if (empty($user->token_2fa)) {
            $google2fa = app('pragmarx.google2fa');
            $token2FA = $google2fa->generateSecretKey();

            $user->token_2fa = $token2FA;
            $user->update();

            $data = [
                'qr' => $google2fa->getQRCodeUrl(config('app.name'), $user->email, $token2FA),
                'token' => $token2FA,
            ];
            $message = '2fa-generate';
        } else {
            $data = ['token' => $user->token_2fa];
            $message = '2fa';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => array_merge($data, $this->tokenData($user, $token)),
        ], 200);
    }

    public function tokenData(User $user, string $token): array
    {
        $permissions = getPermissionsByRole($user);
        $userData = getUserData($user->load([
            'userDetail',
            'supplier.plan.features:id,name',
            'supplier.plan.featurePlans.feature:id,name',
            'supplier.boss.settings',
            'supplier.boss.settings.billing',
            'supplier.boss.settings.agreement',
            'supplier.boss.plan.features:id,name',
            'supplier.boss.plan.featurePlans.feature:id,name',
            'supplier.boss.user.userDetail',
            'supplier.boss.user.commissions',
            'supplier.boss.user.offers',
            'supplier.boss.billings',
            'supplier.boss.agreements',
            'supplier.settings',
            'supplier.settings.billing',
            'supplier.settings.agreement',
            'supplier.agreements',
            'supplier.billings',
            'supplier.user.commissions',
            'supplier.user.offers',
            'offers',
            'commissions',
        ]));

        return [
            'accessToken' => $token,
            'token_type' => 'bearer',
            'user_data' => $userData,
            'userAbilities' => $permissions,
        ];
    }
}
