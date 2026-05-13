<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
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
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        self::overdueTasks();
        self::overdueReminders();

        return 0;
    }

    private function overdueTasks() {

        $tasks = 
            VehicleTask::with(['vehicle', 'user'])
                ->where('is_cost', 0)
                ->whereNotNull('end_date')
                ->whereDate('end_date', '<=', now())
                ->whereHas('vehicle', function($query) {
                    $query->where('state_id', '!=', 12);
                })
                ->get();
        
        foreach($tasks as $task){
            $notificationSettings = $this->getUserNotificationSettings($task->user_id);

            if (!$this->shouldSendReminderNotification($notificationSettings)) {
                $this->info('Reminder notifications disabled for user_id: ' . ($task->user_id ?? 'N/A'));
                continue;
            }
            
            // Prepare data for notification
            $vehicle = $task->vehicle;
            $regNum = $vehicle ? $vehicle->reg_num : 'N/A';
            
            $title = 'Åtgärd försenad';
            $subtitle = $regNum;
            $text = 'Åtgärden "' . $task->measure . '" skulle ha slutförts ' . $task->end_date;
            $color = 'error';
            $icon = 'custom-atgarder-2';
            $route = '/dashboard/admin/stock/edit/' . $task->vehicle_id . '#tab-tasks';
            
            // Create notification directly in database
            try {
                $dbNotification = Notification::create([
                    'user_id' => $task->user_id,
                    'notification_id' => $task->id,
                    'title' => $title,
                    'subtitle' => $subtitle,
                    'text' => $text,                
                    'color' => $color,
                    'icon' => $icon,
                    'route' => $route,
                    'read' => false,
                ]);
                
                // Prepare message for WebSocket
                $message = (object) [
                    'id' => $dbNotification->id,
                    'title' => $title,
                    'subtitle' => $subtitle,
                    'time' => now()->format('H:i:s'),
                    'img' => null,
                    'color' => $color,
                    'icon' => $icon,
                    'text' => $text,
                    'route' => $route,
                    'read' => false,
                ];
                
                // Send WebSocket event
                if ($task->user_id) {
                    $evento = new UserNotificationEvent($message, $task->user_id);
                    Event::dispatch($evento);
                }

                if ($this->shouldSendReminderEmailNotification($notificationSettings)) {
                    $this->sendNotificationInfoEmail($task->user, [
                        'title' => $title,
                        'subtitle' => $subtitle,
                        'text' => $text,
                        'route' => $route,
                    ]);
                }
                
                $this->info('Notification sent successfully for: ' . $task->measure);
            } catch (\Exception $e) {
                $this->error('Error creating notification: ' . $e->getMessage());
            }
        }
        
        $this->info('Total overdue tasks: ' . $tasks->count());
    }

    private function overdueReminders() {

        $reminders = 
            Reminder::with(['user'])
                ->where('is_done', 0)
                ->whereDate('date', '<=', now())
                ->get();
        
        foreach($reminders as $reminder){
            $notificationSettings = $this->getUserNotificationSettings($reminder->user_id);

            if (!$this->shouldSendReminderNotification($notificationSettings)) {
                $this->info('Reminder notifications disabled for user_id: ' . ($reminder->user_id ?? 'N/A'));
                continue;
            }
            
            // Prepare data for notification            
            $title = 'Ånteckningar försenad';
            $subtitle = $reminder->description;
            $formattedReminderDate = $reminder->date;
            if ($reminder->date) {
                try {
                    $formattedReminderDate = \Carbon\Carbon::parse($reminder->date)->format('Y/m/d H:i');
                } catch (\Exception $e) {
                    $formattedReminderDate = $reminder->date;
                }
            }
            $text = 'Ånteckningar "' . $reminder->description . '" skulle ha slutförts ' . $formattedReminderDate;
            $color = 'error';
            $icon = 'custom-coffee-2';
            $route = '/dashboard/panel#reminders';
            
            // Create notification directly in database
            try {
                $dbNotification = Notification::create([
                    'user_id' => $reminder->user_id,
                    'notification_id' => $reminder->id,
                    'title' => $title,
                    'subtitle' => $subtitle,
                    'text' => $text,                
                    'color' => $color,
                    'icon' => $icon,
                    'route' => $route,
                    'read' => false,
                ]);
                
                // Prepare message for WebSocket
                $message = (object) [
                    'id' => $dbNotification->id,
                    'title' => $title,
                    'subtitle' => $subtitle,
                    'time' => now()->format('H:i:s'),
                    'img' => null,
                    'color' => $color,
                    'icon' => $icon,
                    'text' => $text,
                    'route' => $route,
                    'read' => false,
                ];
                
                // Send WebSocket event
                if ($reminder->user_id) {
                    $evento = new UserNotificationEvent($message, $reminder->user_id);
                    Event::dispatch($evento);
                }

                if ($this->shouldSendReminderEmailNotification($notificationSettings)) {
                    $this->sendNotificationInfoEmail($reminder->user, [
                        'title' => $title,
                        'subtitle' => $subtitle,
                        'text' => $text,
                        'route' => $route,
                    ]);
                }
                
                $this->info('Notification sent successfully for: ' . $reminder->description);
            } catch (\Exception $e) {
                $this->error('Error creating notification: ' . $e->getMessage());
            }
        }
        
        $this->info('Total overdue reminders: ' . $reminders->count());
    }

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
            'title' => 'Ny notis',
            'user' => $recipientName,
            'notificationTitle' => $notificationData['title'] ?? 'Ny notis',
            'notificationSubtitle' => $notificationData['subtitle'] ?? null,
            'notificationText' => $notificationData['text'] ?? '',
            'notificationRoute' => $this->resolveNotificationRoute($notificationData['route'] ?? null),
            'notificationDate' => now()->format('Y/m/d H:i'),
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
                'to' => $email,
                'subject' => $subject,
                'error' => $exception->getMessage(),
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
