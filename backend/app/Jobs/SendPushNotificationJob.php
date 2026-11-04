<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\User;
use App\Services\FcmPushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $connection = 'redis';
    public $queue = 'push-notifications';
    public $tries = 3;
    public $timeout = 30;

    public function __construct(
        private readonly int $userId,
        private readonly int $notificationId
    ) {
    }

    public function handle(FcmPushService $fcmPushService): void
    {
        $user = User::query()->find($this->userId);
        $notification = Notification::query()->find($this->notificationId);

        if (! $user || ! $notification) {
            return;
        }

        $fcmPushService->send($user, $notification);
    }
}
