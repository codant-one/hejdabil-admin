<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\PushToken;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

class FcmPushService
{
    public function __construct(
        private readonly Messaging $messaging
    ) {
    }

    public function send(User $user, Notification $notification): void
    {
        $tokens = $user->pushTokens()
            ->where('notifications_enabled', true)
            ->get();

        foreach ($tokens as $token) {
            $message = $this->buildMessage($token, $notification);

            try {
                $this->messaging->send($message);

                $token->update(['last_used_at' => now()]);
            } catch (NotFound $exception) {
                $this->deleteInvalidToken($token, $exception->getMessage());
            } catch (MessagingException $exception) {
                if ($this->isUnregisteredError($exception)) {
                    $this->deleteInvalidToken($token, $exception->getMessage());
                    continue;
                }

                Log::error('FCM push delivery failed', [
                    'user_id' => $user->id,
                    'push_token_id' => $token->id,
                    'notification_id' => $notification->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    private function buildMessage(PushToken $pushToken, Notification $notification): CloudMessage
    {
        $isSessionActive = (bool) $pushToken->session_active;

        $title = $isSessionActive ? $notification->title : 'Bilflogg';
        $text = $isSessionActive ? $notification->text : 'Du har en ny notis';

        $data = [
            'notification_id' => (string) $notification->id,
        ];

        if ($isSessionActive) {
            $data['route'] = (string) ($notification->route ?? '');
            $data['type'] = (string) ($notification->type ?? $notification->icon ?? '');
        }

        $message = CloudMessage::withTarget('token', $pushToken->token)
            ->withNotification(FirebaseNotification::create($title, $text))
            ->withData($data)
            ->withAndroidConfig(AndroidConfig::fromArray([
                'priority' => 'high',
                'notification' => [
                    'channel_id' => 'default',
                ],
            ]))
            ->withApnsConfig(ApnsConfig::fromArray([
                'headers' => [
                    'apns-priority' => '10',
                ],
                'payload' => [
                    'aps' => [
                        'sound' => 'default',
                    ],
                ],
            ]));

        return $message;
    }

    private function isUnregisteredError(MessagingException $exception): bool
    {
        $message = strtoupper($exception->getMessage());

        return str_contains($message, 'UNREGISTERED') || str_contains($message, 'NOTFOUND');
    }

    private function deleteInvalidToken(PushToken $pushToken, string $error): void
    {
        Log::warning('Removing invalid push token after FCM response', [
            'push_token_id' => $pushToken->id,
            'user_id' => $pushToken->user_id,
            'error' => $error,
        ]);

        $pushToken->delete();
    }
}
