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
            $pending = SendPushNotificationJob::dispatch((int) $notification->user_id, (int) $notification->id);

            // afterResponse() only makes sense for web requests. In the console (cron queue worker,
            // artisan commands) "terminating" callbacks do not run until the process exits,
            // so there the job is queued directly on its connection/queue (push-notifications).
            if (! app()->runningInConsole()) {
                $pending->afterResponse();
            }

            unset($pending); // dispatch here (PendingDispatch::__destruct) so errors are caught by the try
        } catch (\Throwable $exception) {
            Log::warning('Failed to dispatch push notification job', [
                'notification_id' => $notification->id,
                'user_id' => $notification->user_id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
