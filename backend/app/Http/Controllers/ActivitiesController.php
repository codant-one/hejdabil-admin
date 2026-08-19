<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use Spatie\Permission\Middlewares\PermissionMiddleware;

use App\Services\ActivityMetadataResolver;
use App\Services\CacheService;

use App\Models\SupplierActivity;

class ActivitiesController extends Controller
{
    private const PLAN_GATED_FEATURES = [
        'clients',
        'suppliers',
        'billings',
        'invoices',
        'stock',
        'sold',
        'agreements',
        'signed-documents',
        'payouts',
        'notes',
        'my-team',
    ];


    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $activityMetadataResolver = new ActivityMetadataResolver();

            $limit = $request->has('limit') ? $request->limit : 10;
        
            $query = SupplierActivity::with([
                                'user' => fn($u) => $u->select('id', 'name', 'last_name', 'email', 'avatar', 'deleted_at')->withTrashed(),
                                'user.userDetail:user_id,avatar_id,logo'
                            ])
                            ->applyFilters(
                                $request->only([
                                    'search',
                                    'orderByField',
                                    'orderBy',
                                    'supplier_id',
                                    'user_id',
                                    'module',
                                    'date_from',
                                    'date_to',
                                ])
                            )
                            ->where(function ($activitiesQuery) {
                                $activitiesQuery
                                    ->whereRaw("LOWER(action_type) NOT LIKE ?", ['%update%'])
                                    ->orWhereNull('metadata')
                                    ->orWhereRaw('JSON_VALID(metadata) = 0')
                                    ->orWhereRaw("JSON_EXTRACT(metadata, '$.old_values') IS NULL")
                                    ->orWhereRaw("JSON_EXTRACT(metadata, '$.new_values') IS NULL")
                                    ->orWhereRaw("JSON_EXTRACT(metadata, '$.old_values') <> JSON_EXTRACT(metadata, '$.new_values')");
                            });

            $this->applyPermissionFilters($query);

            if ($limit == -1) {
                $allActivities = $query->get();
                $activities = new \Illuminate\Pagination\LengthAwarePaginator(
                    $allActivities,
                    $allActivities->count(),
                    max($allActivities->count(), 1),
                    1
                );
            } else {
                $activities = $query->paginate($limit);
            }

            $activities->setCollection(
                $activityMetadataResolver->enrichCollection($activities->getCollection())
            );

            $supplier_id = null;
            $current_supplier_id = null;

            if(Auth::check() && Auth::user()->getRoleNames()[0] === 'Supplier') {
                $supplier_id = Auth::user()->supplier->id;
                $current_supplier_id = Auth::user()->supplier->id;
            } else if(Auth::check() && Auth::user()->getRoleNames()[0] === 'User') {
                $supplier_id = Auth::user()->supplier->boss_id;
                $current_supplier_id = Auth::user()->supplier->id;
            }

            if (
                Auth::check()
                && (
                    Auth::user()->getRoleNames()[0] === 'Supplier'
                    || Auth::user()->getRoleNames()[0] === 'User'
                )
            ) {
                $users = CacheService::getActiveUsersSuppliers($supplier_id, $current_supplier_id);
            } else {
                $users = CacheService::getActiveAdministratorUsers();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'activities' => $activities,
                    'activitiesTotalCount' => $activities->total(),
                    'suppliers' => CacheService::getActiveSuppliers(),
                    'users' => $users
                ]
            ]);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
              'success' => false,
              'message' => 'database_error',
              'exception' => $ex->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): JsonResponse
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }

    private function getCurrentSupplierId(): int
    {
        $user = Auth::user();
        $role = $user->getRoleNames()[0] ?? null;

        return match ($role) {
            'Supplier' => $user?->supplier?->id,
            'User' => $user?->supplier?->boss_id,
            default => $user?->supplier?->id,
        };
    }

    private function applyPermissionFilters($query): void
    {
        $user = Auth::user();

        if (!$user) {
            $query->whereRaw('1 = 0');

            return;
        }

        if ($user->hasAnyRole(['SuperAdmin', 'Administrator']) || $user->can('administrator')) {
            return;
        }

        $query->where(function ($permissionQuery) use ($user) {
            $hasVisibilityCondition = false;

            $allowEntityType = function (string $entityType) use ($permissionQuery, &$hasVisibilityCondition) {
                $permissionQuery->orWhere('entity_type', $entityType);
                $hasVisibilityCondition = true;
            };

            if ($this->canViewWithPlan($user, 'agreements'))
                $allowEntityType('agreements');

            if ($this->canViewWithPlan($user, 'billings'))
                $allowEntityType('billings');

            if ($this->canViewWithPlan($user, 'clients'))
                $allowEntityType('clients');
            
            if ($this->canViewWithPlan($user, 'suppliers'))
                $allowEntityType('suppliers');

            if ($this->canViewWithPlan($user, 'my-team'))
                $allowEntityType('users');

            if ($this->canViewWithPlan($user, 'payouts'))
                $allowEntityType('payouts');

            if ($this->canViewWithPlan($user, 'notes')) {
                $allowEntityType('notes');
                $allowEntityType('comment_notes');
            }

            if ($this->canViewWithPlan($user, 'signed-documents'))
                $allowEntityType('documents');

            if ($this->canViewWithPlan($user, 'sold')) {
                $permissionQuery->orWhere(function ($vehicleQuery) {
                    $vehicleQuery
                        ->where('entity_type', 'vehicles')
                        ->whereRaw("LOWER(COALESCE(action_type, '')) = ?", ['sell_vehicle']);
                });

                $hasVisibilityCondition = true;
            }

            if ($this->canViewWithPlan($user, 'stock')) {
                $permissionQuery->orWhere(function ($vehicleQuery) {
                    $vehicleQuery
                        ->where('entity_type', 'vehicles')
                        ->whereRaw("LOWER(COALESCE(action_type, '')) <> ?", ['sell_vehicle']);
                });

                $hasVisibilityCondition = true;
            }

            if (!$hasVisibilityCondition)
                $permissionQuery->whereRaw('1 = 0');
        });
    }

    private function canViewWithPlan($user, string $subject): bool
    {
        if (!$user->can('view ' . $subject)) {
            return false;
        }

        return $this->hasPlanFeatureAccess($user, $subject);
    }

    private function hasPlanFeatureAccess($user, string $subject): bool
    {
        $role = $user->getRoleNames()[0] ?? null;
        $normalizedSubject = strtolower(trim($subject));

        if (!in_array($normalizedSubject, self::PLAN_GATED_FEATURES, true)) {
            return true;
        }

        if ($role !== 'Supplier' && $role !== 'User') {
            return true;
        }

        $supplierSource = $role === 'User'
            ? $user?->supplier?->boss
            : $user?->supplier;

        if (!$supplierSource?->plan_id) {
            return false;
        }

        if ($normalizedSubject === 'payouts' && $role === 'Supplier' && (int) ($supplierSource?->is_payout ?? 0) !== 1) {
            return false;
        }

        $supplierSource->loadMissing([
            'plan.features:id,name',
            'plan.featurePlans.feature:id,name',
        ]);

        $directFeatures = collect($supplierSource?->plan?->features ?? [])
            ->map(fn ($feature) => strtolower(trim((string) ($feature->name ?? ''))));

        $pivotFeatures = collect($supplierSource?->plan?->featurePlans ?? [])
            ->map(fn ($item) => strtolower(trim((string) ($item?->feature?->name ?? ''))));

        $planFeatures = $directFeatures
            ->merge($pivotFeatures)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($planFeatures)) {
            return false;
        }

        return in_array($normalizedSubject, $planFeatures, true);
    }
}
