<?php

namespace App\Observers;

use App\Jobs\SendPushNotificationJob;
use App\Models\Notification;

class NotificationObserver
{
    public function created(Notification $notification): void
    {
        if (! $notification->user_id) {
            return;
        }

        SendPushNotificationJob::dispatch((int) $notification->user_id, (int) $notification->id);
    }
}
