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

            if(Auth::check() && Auth::user()->getRoleNames()[0] === 'Supplier') {
                $supplier_id = Auth::user()->supplier->id;
            } else if(Auth::check() && Auth::user()->getRoleNames()[0] === 'User') {
                $supplier_id = Auth::user()->supplier->boss_id;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'activities' => $activities,
                    'activitiesTotalCount' => $activities->total(),
                    'suppliers' => CacheService::getActiveSuppliers(),
                    'users' => CacheService::getActiveUsersSuppliers($supplier_id)
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
}
