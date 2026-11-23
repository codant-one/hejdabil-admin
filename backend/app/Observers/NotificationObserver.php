<?php

namespace App\Observers;

use App\Jobs\SendPushNotificationJob;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class NotificationObserver
{
    public function created(Notification $notification): void
    {
        if (! $notification->user_id) {
            return;
        }

        try {
            SendPushNotificationJob::dispatch((int) $notification->user_id, (int) $notification->id)
                ->afterResponse();
        } catch (\Throwable $exception) {
            Log::warning('Failed to dispatch push notification job', [
                'notification_id' => $notification->id,
                'user_id' => $notification->user_id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
