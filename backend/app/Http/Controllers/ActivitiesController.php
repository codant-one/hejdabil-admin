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
use App\Models\User;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\Client;
use App\Models\Billing;
use App\Models\Vehicle;
use App\Models\Agreement;
use App\Models\Document;
use App\Models\Payout;
use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Invoice;
use App\Models\Currency;
use App\Models\Country;
use App\Models\Plan;
use App\Models\HelpCategory;
use App\Models\HelpVideo;
use Illuminate\Broadcasting\Broadcasters\NullBroadcaster;

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

    private const MODULES = [
        'users_admin' => User::class,
        'roles' => Role::class,
        'users' => User::class,
        'suppliers' => Supplier::class,
        'suppliers_invoices' => SupplierInvoice::class,
        'clients' => Client::class,
        'billings' => Billing::class,
        'vehicles' => Vehicle::class,
        'agreements' => Agreement::class,
        'documents' => Document::class,
        'payouts' => Payout::class,
        'brands' => Brand::class,
        'models' => CarModel::class,
        'invoices' => Invoice::class,
        'currencies' => Currency::class,
        'countries' => Country::class,
        'plans' => Plan::class,
        'help_categories' => HelpCategory::class,
        'help_videos' => HelpVideo::class,
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
                            ->where(function ($actionQuery) use ($request) {
                                if ($request->has('action')) {
                                    $actionQuery->where('action_type', 'LIKE', '%' . $request->action . '%');
                                    
                                    //Para no mostrar los eventos de la papelera (trash) 
                                    //cuando se filtra por delete, es decir cuando se usa el index en la papelera
                                    if ($request->action === 'delete') {
                                        $actionQuery->where('entity_type', '!=' ,'trash');
                                    }
                                }
                            })
                            ->where(function ($stateQuery) use ($request) {
                                if ($request->has('state_id')) {
                                    $stateQuery->where('state_id', $request->state_id);
                                }
                            })
                            ->applyFilters(
                                $request->only([
                                    'search',
                                    'orderByField',
                                    'orderBy',
                                    'supplier_id',
                                    'user_id',
                                    'module',
                                    'action',
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
        try {

            $activity = SupplierActivity::with([
                                'user' => fn($u) => $u->select('id', 'name', 'last_name', 'email', 'avatar', 'deleted_at')->withTrashed(),
                                'user.userDetail:user_id,avatar_id,logo'
                            ])->find($id);
        
            if (!$activity)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Registret hittades inte'
                ], 404);

            $notForceDelete = [
                'users',
                'users_admin',
                'clients'
            ];

            //Si no esta en la lista adminte forzar borrado por encima del
            //softdelete.
            if(!in_array($activity->entity_type, $notForceDelete)) {

                $idToRestore = $activity->entity_id;
                $moduleClass = self::MODULES[$activity->entity_type];
                $itemToRestore = $moduleClass::withTrashed()->find($idToRestore);

                if (!$itemToRestore)
                    return response()->json([
                        'success' => false,
                        'feedback' => 'not_found',
                        'message' => 'Registret som skulle tas bort hittades inte'
                    ], 404);

                $itemToRestore->forceDestroy();
            }

            
            $fields = [
                'id',
                'user_id',
                'entity_id',
                'state_id',
                'entity_type',
                'action_type',
                'title',
                'description',
                'icon',
                'route',
                'metadata'
            ];

            $oldValues = $activity->only($fields);
            $metadata = json_decode($activity->metadata);
            $title = $metadata->title ?? $activity->title;

            $activity->state_id = 5; //Remove
            $activity->update();

            SupplierActivity::createActivity([
                'entity_id' => $activity->id,
                'entity_type' => 'trash',
                'action_type' => 'delete_trash',
                'title' => 'Registret #'.$activity->id.' '.$title.' borttagen',
                'description' => 'Registret har tagits bort.',
                'icon' => 'custom-waste',
                'metadata' => json_encode([
                    'activity_id' => $activity->id,
                    'title' => 'Registret #'.$activity->id.' '.$title,
                    'old_values' => $oldValues
                ])
            ]);

            SupplierActivity::where('entity_id', $activity->id)
                ->where('entity_type', 'trash')
                ->update(['route' => null]);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'activity' => $activity
                ]
            ], 200);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error',
                'exception' => $ex->getMessage()
            ], 500);
        }
    }

    /**
     * Restore deleted Item
     */ 
    public function restoreItem($id): JsonResponse
    {
        try {

            $activity = SupplierActivity::find($id);

            if (!$activity)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Registret hittades inte'
                ], 404);

            $idToRestore = $activity->entity_id;
            $moduleClass = self::MODULES[$activity->entity_type];
            $itemToRestore = $moduleClass::withTrashed()->find($idToRestore);

            if (!$itemToRestore)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Registret som skulle återställas hittades inte'
                ], 404);

            $itemToRestore->restore();
            $activity->update(['state_id' => 1]);

            $metadata = json_decode($activity->metadata);   
            $title = $metadata->title ?? $activity->title;

            if (method_exists($moduleClass, 'restoreItem')) {
                $moduleClass::restoreItem($idToRestore, $metadata);
            }

            SupplierActivity::createActivity([
                'entity_id' => $activity->id,
                'entity_type' => 'trash',
                'action_type' => 'update_trash',
                'title' => 'Registret #'.$itemToRestore->id.' '.$title.' (borttaget) återställt',
                'description' => 'Ett borttaget register har återställts.',
                'icon' => 'custom-waste',
                'route' => null,
                'metadata' => json_encode([
                    'activity_id' => $activity->id,
                    'title' => 'Registret #'.$activity->id.' '.$title,
                ])
            ]);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'activity' => $activity
                ]
            ], 200);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error',
                'exception' => $ex->getMessage()
            ], 500);
        }
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
