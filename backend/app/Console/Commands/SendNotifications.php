<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

use App\Models\VehicleTask;
use App\Models\Notification;
use App\Models\Setting;
use App\Models\SettingNotification;
use App\Models\Reminder;

use App\Events\UserNotificationEvent;

class SendNotifications extends Command
{
    private const DEFAULT_SEND_REMINDERS = true;
    private const DEFAULT_NOTIFY_VIA_EMAIL = false;
    private const DEFAULT_HOURS = 24;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications to users';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->overdueTasks();
        $this->overdueReminders();

        return 0;
    }

    /* ---------------------------------------------------------------------
     |  Tasks
     | ------------------------------------------------------------------- */

    private function overdueTasks(): void
    {
        $now = now();
        $maxHours = $this->resolveMaxNotificationHours();

        $tasks = VehicleTask::with(['vehicle', 'user'])
            ->where('is_cost', 0)
            ->whereNotNull('end_date')
            ->whereNull('notified_at')
            ->where('end_date', '>', $now)
            ->where('end_date', '<=', $now->copy()->addHours($maxHours))
            ->whereHas('vehicle', function ($query) {
                $query->where('state_id', '!=', 12);
            })
            ->get();

        $sent = 0;

        foreach ($tasks as $task) {
            try {
                $dueAt = Carbon::parse($task->end_date);
            } catch (\Exception $e) {
                $this->error('Error parsing task end_date for task_id ' . $task->id . ': ' . $e->getMessage());
                continue;
            }

            $ok = $this->processDueItem($task, $dueAt, $now, function (string $remainingText) use ($task, $dueAt) {
                $regNum = $task->vehicle ? $task->vehicle->reg_num : 'N/A';

                return [
                    'title'    => 'Åtgärd förfaller snart',
                    'subtitle' => $regNum,
                    'text'     => 'Åtgärden "' . $task->measure . '" förfaller om ca ' . $remainingText . ' (' . $dueAt->format('Y/m/d H:i') . ').',
                    'color'    => 'error',
                    'icon'     => 'custom-atgarder-2',
                    'route'    => '/dashboard/admin/stock/edit/' . $task->vehicle_id . '#tab-tasks',
                ];
            });

            if ($ok) {
                $sent++;
            }
        }

        $this->info('Total upcoming tasks evaluated: ' . $tasks->count() . ' | sent: ' . $sent);
    }

    /* ---------------------------------------------------------------------
     |  Reminders
     | ------------------------------------------------------------------- */

    private function overdueReminders(): void
    {
        $now = now();
        $maxHours = $this->resolveMaxNotificationHours();

        $reminders = Reminder::with(['user'])
            ->where('is_done', 0)
            ->whereNotNull('date')
            ->whereNull('notified_at')
            ->where('date', '>', $now)
            ->where('date', '<=', $now->copy()->addHours($maxHours))
            ->get();

        $sent = 0;

        foreach ($reminders as $reminder) {
            try {
                $dueAt = Carbon::parse($reminder->date);
            } catch (\Exception $e) {
                $this->error('Error parsing reminder date for reminder_id ' . $reminder->id . ': ' . $e->getMessage());
                continue;
            }

            $ok = $this->processDueItem($reminder, $dueAt, $now, function (string $remainingText) use ($reminder, $dueAt) {
                return [
                    'title'    => 'Anteckning förfaller snart',
                    'subtitle' => $reminder->description,
                    'text'     => 'Anteckning "' . $reminder->description . '" förfaller om ca ' . $remainingText . ' (' . $dueAt->format('Y/m/d H:i') . ').',
                    'color'    => 'error',
                    'icon'     => 'custom-coffee-2',
                    'route'    => '/dashboard/panel#reminders',
                ];
            });

            if ($ok) {
                $sent++;
            }
        }

        $this->info('Total upcoming reminders evaluated: ' . $reminders->count() . ' | sent: ' . $sent);
    }

    /* ---------------------------------------------------------------------
     |  Core logic (shared by tasks and reminders)
     | ------------------------------------------------------------------- */

    /**
     * Evaluates one item and, if it is inside the user's notification window
     * and has not been notified yet, creates the notification.
     *
     * @param  Model     $item          Reminder or VehicleTask (must have user_id, user, notified_at)
     * @param  Carbon    $dueAt         Moment the item is due
     * @param  Carbon    $now
     * @param  callable  $buildPayload  fn(string $remainingText): array{title,subtitle,text,color,icon,route}
     * @return bool                     true if a notification was sent
     */
    private function processDueItem(Model $item, Carbon $dueAt, Carbon $now, callable $buildPayload): bool
    {
        $userId = $item->user_id;
        $notificationSettings = $this->getUserNotificationSettings($userId);

        if (!$this->shouldSendReminderNotification($notificationSettings)) {
            $this->info('Reminder notifications disabled for user_id: ' . ($userId ?? 'N/A'));
            return false;
        }

        $hours = $this->normalizeHours($this->configHours($notificationSettings));
        $remainingMinutes = $this->minutesUntil($dueAt, $now);

        // Send as soon as we are inside the window "hours before due" and the event has not passed.
        if ($remainingMinutes <= 0 || $remainingMinutes > $hours * 60) {
            return false;
        }

        // Atomically "claim" the item so overlapping runs can never send it twice.
        if (!$this->claimItem($item, $now)) {
            return false;
        }

        $payload = $buildPayload($this->formatRemaining($remainingMinutes));

        // 1) Persist notification in DB
        try {
            $dbNotification = Notification::create([
                'user_id'         => $userId,
                'notification_id' => $item->id,
                'title'           => $payload['title'],
                'subtitle'        => $payload['subtitle'],
                'text'            => $payload['text'],
                'color'           => $payload['color'],
                'icon'            => $payload['icon'],
                'route'           => $payload['route'],
                'read'            => false,
            ]);
        } catch (\Exception $e) {
            $this->releaseItem($item); // allow retry on next run
            $this->error('Error creating notification: ' . $e->getMessage());
            return false;
        }

        // 2) WebSocket + email (a failure here must not trigger a duplicate DB notification)
        try {
            if ($userId) {
                $message = (object) [
                    'id'       => $dbNotification->id,
                    'title'    => $payload['title'],
                    'subtitle' => $payload['subtitle'],
                    'time'     => now()->format('H:i:s'),
                    'img'      => null,
                    'color'    => $payload['color'],
                    'icon'     => $payload['icon'],
                    'text'     => $payload['text'],
                    'route'    => $payload['route'],
                    'read'     => false,
                ];

                Event::dispatch(new UserNotificationEvent($message, $userId));
            }

            if ($this->shouldSendReminderEmailNotification($notificationSettings)) {
                $this->sendNotificationInfoEmail($item->user, [
                    'title'    => $payload['title'],
                    'subtitle' => $payload['subtitle'],
                    'text'     => $payload['text'],
                    'route'    => $payload['route'],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error dispatching notification event/email', [
                'user_id' => $userId,
                'item_id' => $item->id,
                'error'   => $e->getMessage(),
            ]);
            $this->error('Error dispatching notification: ' . $e->getMessage());
        }

        $this->info('Notification sent for ' . class_basename($item) . ' #' . $item->id . ' (' . $remainingMinutes . ' min remaining)');

        return true;
    }

    /**
     * Marks the item as notified only if nobody else did it first.
     * Uses the base query builder so updated_at is not touched.
     */
    private function claimItem(Model $item, Carbon $now): bool
    {
        $affected = $item->newQuery()
            ->whereKey($item->getKey())
            ->whereNull('notified_at')
            ->toBase()
            ->update(['notified_at' => $now]);

        return $affected === 1;
    }

    private function releaseItem(Model $item): void
    {
        $item->newQuery()
            ->whereKey($item->getKey())
            ->toBase()
            ->update(['notified_at' => null]);
    }

    /**
     * Whole minutes from $now until $dueAt (negative if already past).
     * Uses timestamps so it behaves the same on Carbon 2 and Carbon 3.
     */
    private function minutesUntil(Carbon $dueAt, Carbon $now): int
    {
        return (int) floor(($dueAt->getTimestamp() - $now->getTimestamp()) / 60);
    }

    private function formatRemaining(int $minutes): string
    {
        if ($minutes >= 60) {
            return (int) round($minutes / 60) . ' tim';
        }

        return max(1, $minutes) . ' min';
    }

    /* ---------------------------------------------------------------------
     |  Settings helpers
     | ------------------------------------------------------------------- */

    private function getUserNotificationSettings($userId): ?SettingNotification
    {
        if (!$userId) {
            return null;
        }

        $settings = Setting::query()
            ->with('notification')
            ->where('user_id', $userId)
            ->first();

        return $settings?->notification;
    }

    private function configHours(?SettingNotification $notificationSettings): int
    {
        if (!$notificationSettings) {
            return self::DEFAULT_HOURS;
        }

        return (int) ($notificationSettings->hours ?? self::DEFAULT_HOURS);
    }

    private function resolveMaxNotificationHours(): int
    {
        $maxConfiguredHours = (int) SettingNotification::query()->max('hours');

        return max(1, self::DEFAULT_HOURS, $maxConfiguredHours);
    }

    private function normalizeHours(int $hours): int
    {
        return $hours > 0 ? $hours : self::DEFAULT_HOURS;
    }

    private function shouldSendReminderNotification(?SettingNotification $notificationSettings): bool
    {
        if (!$notificationSettings) {
            return self::DEFAULT_SEND_REMINDERS;
        }

        return (int) ($notificationSettings->send_reminders ?? (self::DEFAULT_SEND_REMINDERS ? 1 : 0)) === 1;
    }

    private function shouldSendReminderEmailNotification(?SettingNotification $notificationSettings): bool
    {
        if (!$notificationSettings) {
            return self::DEFAULT_NOTIFY_VIA_EMAIL;
        }

        return (int) ($notificationSettings->notify_via_email ?? (self::DEFAULT_NOTIFY_VIA_EMAIL ? 1 : 0)) === 1;
    }

    /* ---------------------------------------------------------------------
     |  Email
     | ------------------------------------------------------------------- */

    private function sendNotificationInfoEmail($user, array $notificationData): void
    {
        $email = $user->email ?? null;

        if (!$email) {
            return;
        }

        $fullName = trim(($user->name ?? '') . ' ' . ($user->last_name ?? ''));
        $recipientName = $fullName !== '' ? $fullName : ($user->name ?? $email);

        $subject = trim(($notificationData['title'] ?? 'Ny notis') . (!empty($notificationData['subtitle']) ? ' - ' . $notificationData['subtitle'] : ''));

        $viewData = [
            'title'                => 'Ny notis',
            'user'                 => $recipientName,
            'notificationTitle'    => $notificationData['title'] ?? 'Ny notis',
            'notificationSubtitle' => $notificationData['subtitle'] ?? null,
            'notificationText'     => $notificationData['text'] ?? '',
            'notificationRoute'    => $this->resolveNotificationRoute($notificationData['route'] ?? null),
            'notificationDate'     => now()->format('Y/m/d H:i'),
        ];

        $fromAddress = config('mail.from.address');
        $fromName = config('mail.from.name');

        try {
            Mail::send('emails.notifications.info', $viewData, function ($message) use ($email, $subject, $fromAddress, $fromName) {
                if (!empty($fromAddress)) {
                    $message->from($fromAddress, $fromName);
                }

                $message->to($email)->subject($subject);
            });
        } catch (\Exception $exception) {
            Log::error('Error sending notification email', [
                'to'      => $email,
                'subject' => $subject,
                'error'   => $exception->getMessage(),
            ]);

            $this->error('Error sending notification email to ' . $email . ': ' . $exception->getMessage());
        }
    }

    private function resolveNotificationRoute(?string $route): ?string
    {
        if (!$route) {
            return null;
        }

        if (str_starts_with($route, 'http://') || str_starts_with($route, 'https://')) {
            return $route;
        }

        $appDomain = rtrim((string) config('app.domain', config('app.url')), '/');

        if ($appDomain === '') {
            return $route;
        }

        return $appDomain . '/' . ltrim($route, '/');
    }
}