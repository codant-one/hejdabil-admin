<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Artisan; 

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('billings:update-state')
                 ->daily()
                 ->at('00:00');

        $schedule->command('reminders:send')
                 ->daily()
                 ->at('00:00');

        $schedule->command('notifications:send')
                 ->hourly();

        $schedule->command('reminders:delete --hours=24')
                 ->hourly();

        $schedule->command('supplier:generate-billing')
                 ->daily()
                 ->at('06:06')
                 ->after(function () {
                    Artisan::call('suppliers:update-plan-date');
                 })
                 ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
