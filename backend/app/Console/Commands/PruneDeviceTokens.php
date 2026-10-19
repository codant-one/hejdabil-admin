<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\DeviceToken;

class PruneDeviceTokens extends Command
{
    protected $signature = 'device-tokens:prune {--days=30 : Days to keep revoked or expired tokens}';

    protected $description = 'Delete revoked or expired device tokens older than the given number of days';

    public function handle()
    {
        $limit = now()->subDays((int) $this->option('days'));

        $deleted = DeviceToken::query()
            ->where(function ($query) use ($limit) {
                $query->where('revoked_at', '<=', $limit)
                    ->orWhere('expires_at', '<=', $limit);
            })
            ->delete();

        $this->info("Deleted device tokens: {$deleted}");

        return 0;
    }
}
