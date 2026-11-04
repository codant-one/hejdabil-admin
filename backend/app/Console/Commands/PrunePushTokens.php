<?php

namespace App\Console\Commands;

use App\Models\PushToken;
use Illuminate\Console\Command;

class PrunePushTokens extends Command
{
    protected $signature = 'push-tokens:prune {--days=60 : Days since last use}';

    protected $description = 'Delete push tokens that were not used within the configured number of days';

    public function handle(): int
    {
        $limit = now()->subDays((int) $this->option('days'));

        $deleted = PushToken::query()
            ->whereNotNull('last_used_at')
            ->where('last_used_at', '<=', $limit)
            ->delete();

        $this->info("Deleted push tokens: {$deleted}");

        return self::SUCCESS;
    }
}
