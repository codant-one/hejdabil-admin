<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class MonitorPerformance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:performance {--clear-cache : Clear all cache}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor application performance metrics';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('clear-cache')) {
            $this->clearAllCache();
            return;
        }

        $this->info('=== HEJDABIL ADMIN - PERFORMANCE MONITOR ===');
        $this->newLine();

        $this->checkRedis();
        $this->checkDatabase();
        $this->checkQueue();
        $this->checkCache();
        
        $this->newLine();
        $this->info('Monitor completed successfully!');
    }

    protected function checkRedis()
    {
        $this->info('📊 REDIS STATUS:');
        
        try {
            $redis = Redis::connection();
            $info = $redis->info();
            
            $this->line("  ✓ Connected: YES");
            $this->line("  • Memory Used: " . $this->formatBytes($info['used_memory'] ?? 0));
            $this->line("  • Connected Clients: " . ($info['connected_clients'] ?? 'N/A'));
            $this->line("  • Total Commands: " . number_format($info['total_commands_processed'] ?? 0));
            
        } catch (\Exception $e) {
            $this->error("  ✗ Redis Error: " . $e->getMessage());
        }
        
        $this->newLine();
    }

    protected function checkDatabase()
    {
        $this->info('💾 DATABASE STATUS:');
        
        try {
            $connection = DB::connection();
            $pdo = $connection->getPdo();
            
            $this->line("  ✓ Connected: YES");
            $this->line("  • Driver: " . $connection->getDriverName());
            $this->line("  • Database: " . $connection->getDatabaseName());
            
            // Query counts
            $vehicles = DB::table('vehicles')->count();
            $agreements = DB::table('agreements')->count();
            $billings = DB::table('billings')->count();
            
            $this->line("  • Vehicles: " . number_format($vehicles));
            $this->line("  • Agreements: " . number_format($agreements));
            $this->line("  • Billings: " . number_format($billings));
            
        } catch (\Exception $e) {
            $this->error("  ✗ Database Error: " . $e->getMessage());
        }
        
        $this->newLine();
    }

    protected function checkQueue()
    {
        $this->info('⚙️  QUEUE STATUS:');
        
        try {
            $failed = DB::table('failed_jobs')->count();
            $pending = DB::table('jobs')->count();
            
            $this->line("  • Pending Jobs: " . number_format($pending));
            $this->line("  • Failed Jobs: " . number_format($failed));
            
            if ($failed > 0) {
                $this->warn("  ⚠ There are failed jobs. Run: php artisan queue:retry all");
            }
            
            if ($pending > 100) {
                $this->warn("  ⚠ Queue is building up. Check if worker is running.");
            }
            
        } catch (\Exception $e) {
            $this->error("  ✗ Queue Error: " . $e->getMessage());
        }
        
        $this->newLine();
    }

    protected function checkCache()
    {
        $this->info('🗄️  CACHE STATUS:');
        
        try {
            $cacheKeys = [
                'brands.all',
                'car_models.all',
                'gearboxes.all',
                'suppliers.active',
            ];
            
            $cachedCount = 0;
            foreach ($cacheKeys as $key) {
                if (Cache::has($key)) {
                    $cachedCount++;
                }
            }
            
            $this->line("  • Driver: " . config('cache.default'));
            $this->line("  • Cached Catalogs: {$cachedCount}/" . count($cacheKeys));
            
            if ($cachedCount < count($cacheKeys)) {
                $this->warn("  ℹ Some catalogs are not cached yet. They will be cached on first request.");
            }
            
        } catch (\Exception $e) {
            $this->error("  ✗ Cache Error: " . $e->getMessage());
        }
        
        $this->newLine();
    }

    protected function clearAllCache()
    {
        $this->info('🧹 Clearing all cache...');
        
        try {
            \Artisan::call('cache:clear');
            $this->line('  ✓ Application cache cleared');
            
            \Artisan::call('config:clear');
            $this->line('  ✓ Config cache cleared');
            
            \Artisan::call('route:clear');
            $this->line('  ✓ Route cache cleared');
            
            \Artisan::call('view:clear');
            $this->line('  ✓ View cache cleared');
            
            $this->newLine();
            $this->info('All cache cleared successfully!');
            
        } catch (\Exception $e) {
            $this->error('Error clearing cache: ' . $e->getMessage());
        }
    }

    protected function formatBytes($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }
}
