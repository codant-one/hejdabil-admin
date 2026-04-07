<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Notification;
use App\Models\Vehicle;
use App\Models\Agreement;
use App\Models\Document;

class DeleteNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete notifications from the system';

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
        self::deleteNotifications();

        return 0;
    }

    private function deleteNotifications() {
        $routesToDelete = [];

        Notification::query()
            ->whereNotNull('route')
            ->select('id', 'route')
            ->chunkById(100, function ($notifications) use (&$routesToDelete) {
                foreach ($notifications as $notification) {
                    $routeFragment = $this->resolveInvalidRouteFragment($notification->route);

                    if ($routeFragment) {
                        $routesToDelete[$routeFragment] = $routeFragment;
                    }
                }
            });

        foreach ($routesToDelete as $routeFragment) {
            Notification::deleteNotificationsByRoute($routeFragment);
            $this->info('Deleted notifications for route: ' . $routeFragment);
        }

        $this->info('Total invalid route groups deleted: ' . count($routesToDelete));
    }

    private function resolveInvalidRouteFragment($route)
    {
        if (!$route) {
            return null;
        }

        if (preg_match('#stock/edit/(\d+)#', $route, $matches)) {
            $vehicleId = (int) $matches[1];

            return Vehicle::whereKey($vehicleId)->exists()
                ? null
                : 'stock/edit/' . $vehicleId;
        }

        $parsedRoute = parse_url($route);
        $path = $parsedRoute['path'] ?? '';
        $query = $parsedRoute['query'] ?? '';

        if (!$path || !$query) {
            return null;
        }

        parse_str($query, $queryParams);
        $fileId = isset($queryParams['file_id']) ? (int) $queryParams['file_id'] : null;

        if (!$fileId) {
            return null;
        }

        if (str_contains($path, '/agreements')) {
            return Agreement::whereKey($fileId)->exists()
                ? null
                : 'agreements?file_id=' . $fileId;
        }

        if (str_contains($path, '/documents')) {
            return Document::whereKey($fileId)->exists()
                ? null
                : 'documents?file_id=' . $fileId;
        }

        return null;
    }

}
