<?php

namespace App\Jobs;

use App\Events\UserNotificationEvent;
use App\Models\AdminNotification;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationTemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class SendBroadcastNotificationChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const DEFAULT_ROUTE = '/dashboard/panel';
    public const ICON = 'custom-alarm';

    public $tries = 3;
    public $timeout = 50; // < retry_after (90) and < the cron's --max-time=55

    /**
     * @param  array<int, int>  $userIds
     */
    public function __construct(
        public readonly int $broadcastId,
        public readonly array $userIds,
        public readonly bool $isLastChunk = false
    ) {
        $this->onConnection(config('queue.push_connection'));
        $this->onQueue('push-notifications');
    }

    public function handle(NotificationTemplateRenderer $renderer): void
    {
        $broadcast = AdminNotification::query()->find($this->broadcastId);

        if (! $broadcast) {
            return;
        }

        AdminNotification::query()
            ->whereKey($broadcast->id)
            ->where('state_id', AdminNotification::stateId(AdminNotification::STATE_QUEUED))
            ->update(['state_id' => AdminNotification::stateId(AdminNotification::STATE_SENDING)]);

        $failures = 0;

        // Only users that are still active (SoftDeletes) when the chunk is processed.
        $users = User::query()->whereIn('id', $this->userIds)->get(['id', 'name', 'last_name']);

        foreach ($users as $user) {
            try {
                $this->deliverTo($broadcast, $user, $renderer);
            } catch (\Throwable $exception) {
                $failures++;

                Log::error('Broadcast notification failed for user', [
                    'broadcast_id' => $broadcast->id,
                    'user_id' => $user->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        if ($failures > 0) {
            $this->markFailed();
        }

        $this->finish();
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('Broadcast notification chunk job failed', [
            'broadcast_id' => $this->broadcastId,
            'error' => $exception?->getMessage(),
        ]);

        $this->markFailed();
    }

    private function deliverTo(AdminNotification $broadcast, User $user, NotificationTemplateRenderer $renderer): void
    {
        $title = $renderer->render($broadcast->title, $user);
        $subtitle = $renderer->render($broadcast->subtitle, $user);
        $route = $broadcast->route ?: self::DEFAULT_ROUTE;

        try {
            // One Eloquent create per user: fires NotificationObserver::created (Android/iOS push).
            // If it already exists (retry), nothing is created and the Observer does not fire again.
            $notification = Notification::query()->firstOrCreate(
                [
                    'broadcast_id' => $broadcast->id,
                    'user_id' => $user->id,
                ],
                [
                    'title' => $title,
                    'subtitle' => $subtitle,
                    'text' => $subtitle,
                    'color' => 'primary',
                    'icon' => self::ICON,
                    'route' => $route,
                    'read' => false,
                ]
            );
        } catch (UniqueConstraintViolationException) {
            return; // created by a concurrent run: web + push already handled
        }

        if (! $notification->wasRecentlyCreated) {
            return;
        }

        // Real-time bell (same channel as NotificationController::send).
        // A websocket error does not count as a failure: the notification is already stored.
        try {
            Event::dispatch(new UserNotificationEvent((object) [
                'id' => $notification->id,
                'title' => $title,
                'subtitle' => $subtitle,
                'time' => now()->format('H:i:s'),
                'img' => null,
                'color' => $notification->color,
                'icon' => $notification->icon,
                'text' => $subtitle,
                'route' => $route,
                'read' => false,
            ], $user->id));
        } catch (\Throwable $exception) {
            Log::warning('Broadcast websocket event failed', [
                'broadcast_id' => $broadcast->id,
                'notification_id' => $notification->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function markFailed(): void
    {
        AdminNotification::query()
            ->whereKey($this->broadcastId)
            ->update(['state_id' => AdminNotification::stateId(AdminNotification::STATE_FAILED)]);
    }

    private function finish(): void
    {
        $broadcast = AdminNotification::query()->find($this->broadcastId);

        if (! $broadcast) {
            return;
        }

        // Last chunk, or a late retry of a chunk after completion: recount actual notifications.
        if (! $this->isLastChunk && ! $broadcast->hasState(AdminNotification::STATE_SENT)) {
            return;
        }

        $updates = [
            'recipients_count' => Notification::query()->where('broadcast_id', $broadcast->id)->count(),
        ];

        if (! $broadcast->hasState(AdminNotification::STATE_FAILED)) {
            $updates['state_id'] = AdminNotification::stateId(AdminNotification::STATE_SENT);
            $updates['sent_at'] = $broadcast->sent_at ?? now();
        }

        $broadcast->update($updates);
    }
}
