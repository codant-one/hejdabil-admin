<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

use App\Models\Reminder;

class DeleteReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:delete {--hours=24 : Minimum hours since reminder was completed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete completed reminders after a retention window';

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
        $hours = max(0, (int) $this->option('hours'));

        $this->deleteReminders($hours);

        return 0;
    }

    private function deleteReminders(int $hours): void
    {
        $deleteBefore = Carbon::now()->subHours($hours);

        $reminders = Reminder::query()
            ->where('is_done', 1)
            ->whereNotNull('completed_at')
            ->where('completed_at', '<=', $deleteBefore)
            ->get();

        $reminders->each->delete();

        $this->info('Total completed reminders deleted: ' . $reminders->count());
    }

}
