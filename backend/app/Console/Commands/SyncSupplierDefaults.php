<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;

use App\Models\Supplier;

class SyncSupplierDefaults extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'suppliers:sync-defaults {--dry-run : Show changes without writing to database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync default supplier permissions and plans for supplier users';

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
        $this->permissions();
        $this->plans();

        return self::SUCCESS;
    }

    private function permissions() {
        $defaultPermissions = collect(Supplier::PERMISSIONS)
            ->filter()
            ->unique()
            ->values();

        if ($defaultPermissions->isEmpty()) {
            $this->warn('No default permissions found in Supplier::PERMISSIONS');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');

        foreach ($defaultPermissions as $permissionName) {
            Permission::findOrCreate($permissionName, 'api');
        }

        $suppliers = Supplier::withTrashed()
            ->with(['user' => function ($query) {
                $query->with('permissions', 'roles');
            }])
            ->where('plan_id', '<>', 1)
            ->whereNotNull('boss_id')
            ->get();

        $updatedUsers = 0;
        $unchangedUsers = 0;

        $suppliers->each(function ($supplier) use ($defaultPermissions, $dryRun, &$updatedUsers, &$unchangedUsers) {
            $user = $supplier->user;

            if (!$user) {
                return;
            }

            $currentPermissions = $user->getDirectPermissions()
                ->pluck('name')
                ->filter()
                ->values();

            $mergedPermissions = $currentPermissions
                ->merge($defaultPermissions)
                ->unique()
                ->values();

            if ($mergedPermissions->values()->all() === $currentPermissions->values()->all()) {
                $unchangedUsers++;
                return;
            }

            if (!$dryRun) {
                $user->syncPermissions($mergedPermissions->all());
            }

            $updatedUsers++;
        });

        $this->info('Default permissions: ' . $defaultPermissions->implode(', '));
        $this->info('Suppliers scanned: ' . $suppliers->count());
        $this->info('Users updated: ' . $updatedUsers . ($dryRun ? ' (dry-run)' : ''));
        $this->info('Users unchanged: ' . $unchangedUsers);

        return self::SUCCESS;
    }

    private function plans() {
        $dryRun = (bool) $this->option('dry-run');

        $suppliers = Supplier::withTrashed()
            ->with(['boss:id,plan_id'])
            ->whereNotNull('boss_id')
            ->whereNull('plan_id')
            ->get();

        $updatedSuppliers = 0;
        $unchangedSuppliers = 0;
        $skippedWithoutBossPlan = 0;

        $suppliers->each(function ($supplier) use ($dryRun, &$updatedSuppliers, &$unchangedSuppliers, &$skippedWithoutBossPlan) {
            $bossPlanId = $supplier->boss?->plan_id;

            if (empty($bossPlanId)) {
                $skippedWithoutBossPlan++;
                return;
            }

            if ((int) $supplier->plan_id === (int) $bossPlanId) {
                $unchangedSuppliers++;
                return;
            }

            if (!$dryRun) {
                $supplier->update([
                    'plan_id' => $bossPlanId,
                ]);
            }

            $updatedSuppliers++;
        });

        $this->info('Plan sync scanned: ' . $suppliers->count());
        $this->info('Suppliers updated with boss plan: ' . $updatedSuppliers . ($dryRun ? ' (dry-run)' : ''));
        $this->info('Suppliers unchanged: ' . $unchangedSuppliers);
        $this->info('Suppliers skipped (boss without plan): ' . $skippedWithoutBossPlan);
    }

}
