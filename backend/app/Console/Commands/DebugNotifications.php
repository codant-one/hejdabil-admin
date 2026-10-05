<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

use App\Models\Reminder;
use App\Models\Setting;

class DebugNotifications extends Command
{
    protected $signature = 'notifications:debug';
    protected $description = 'Diagnostic info for reminder notifications';

    public function handle()
    {
        $now = now();

        $this->info('Server now():      ' . $now->format('Y-m-d H:i:s'));
        $this->info('app.timezone:       ' . config('app.timezone'));
        $this->info('PHP default tz:     ' . date_default_timezone_get());
        $this->info('reminders.notified_at exists: ' . (Schema::hasColumn('reminders', 'notified_at') ? 'YES' : 'NO  <-- run php artisan migrate'));
        $this->info('vehicle_tasks.notified_at exists: ' . (Schema::hasColumn('vehicle_tasks', 'notified_at') ? 'YES' : 'NO  <-- run php artisan migrate'));
        $this->line('');

        $hasCol = Schema::hasColumn('reminders', 'notified_at');

        $reminders = Reminder::query()
            ->where('is_done', 0)
            ->whereNotNull('date')
            ->where('date', '>=', $now->copy()->subDay())
            ->orderBy('date')
            ->get();

        if ($reminders->isEmpty()) {
            $this->warn('No pending reminders found.');
            return 0;
        }

        foreach ($reminders as $r) {
            $setting = Setting::with('notification')->where('user_id', $r->user_id)->first();
            $n = $setting?->notification;
            $hours = $n && (int) $n->hours > 0 ? (int) $n->hours : 24;
            $send = $n ? (int) $n->send_reminders === 1 : true;

            $remaining = (int) floor((strtotime((string) $r->date) - $now->getTimestamp()) / 60);

            $reason = 'WILL SEND on next run';
            if (!$send) {
                $reason = 'SKIPPED: user has send_reminders disabled';
            } elseif ($hasCol && $r->notified_at) {
                $reason = 'SKIPPED: already notified at ' . $r->notified_at;
            } elseif ($remaining <= 0) {
                $reason = 'SKIPPED: due date already passed';
            } elseif ($remaining > $hours * 60) {
                $reason = 'WAITING: outside window (will send when <= ' . $hours * 60 . ' min remain)';
            }

            $this->line(sprintf(
                '#%d "%s" | date=%s | remaining=%d min | user_id=%s | hours=%d | %s',
                $r->id, $r->description, $r->date, $remaining, $r->user_id ?? 'NULL', $hours, $reason
            ));
        }

        return 0;
    }
}