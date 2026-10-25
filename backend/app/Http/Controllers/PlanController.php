<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlanRequest;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

use Spatie\Permission\Middlewares\PermissionMiddleware;

use App\Models\Plan;
use App\Models\Feature;
use App\Models\SupplierActivity;

class PlanController extends Controller
{
    public function __construct()
    {
        $this->middleware(PermissionMiddleware::class . ':view plans|administrator')->only(['index']);
        $this->middleware(PermissionMiddleware::class . ':create plans|administrator')->only(['store']);
        $this->middleware(PermissionMiddleware::class . ':edit plans|administrator')->only(['update']);
        $this->middleware(PermissionMiddleware::class . ':delete plans|administrator')->only(['destroy']);
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $features = Feature::all();
            $limit = $request->has('limit') ? $request->limit : 10;

            $plans = Plan::with(['state:id,name', 'featurePlans.feature:id,name'])
                    ->withTrashed()
                    ->applyFilters(
                        $request->only([
                            'search',
                            'orderByField',
                            'orderBy',
                            'state_id'
                        ])
                    );
            
            
            if ($limit == -1) {
                    $allPlans = $plans->get();
                    $plans = new \Illuminate\Pagination\LengthAwarePaginator(
                        $allPlans,
                        $allPlans->count(),
                        max($allPlans->count(), 1),
                        1
                    );
                } else {
                    $plans = $plans->paginate($limit);
                }

                return response()->json([
                'success' => true,
                'data' => [
                    'plans' => $plans,
                    'features' => $features,
                    'plansTotalCount' => $plans->total()
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

    public function show($id)
    {
        try {

            $plan = Plan::with(['state:id,name'])
                                ->withTrashed()
                                ->find($id);

            if (!$plan)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Planen hittades inte'
                ], 404);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'plan' => $plan
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

    public function store(PlanRequest $request)
    {
        try {
            $plan = Plan::createPlan($request);

            SupplierActivity::createActivity([
                'entity_id' => $plan->id,
                'entity_type' => 'plans',
                'action_type' => 'create_plan',
                'title' => 'Plan #'.$plan->id.' '.$plan->name.' tillagd',
                'description' => 'En ny plan har lagts till.',
                'icon' => 'custom-cash',
                'route' => null, //'/dashboard/admin/plans/'.$plan->id,
                'metadata' => json_encode([
                    'plan_id' => $plan->id,
                    'new_values' => $this->activityValues($plan)
                ])
            ]);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'plan' => Plan::with(['state:id,name'])->find($plan->id)
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

    public function update(PlanRequest $request,  $id): JsonResponse
    {
        try {
            $plan = Plan::withTrashed()->find($id);

            if (!$plan) {
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Planen hittades inte'
                ], 404);
            }

            $oldValues = $this->activityValues($plan);

            $plan = Plan::updatePlan($request, $plan);

            SupplierActivity::createActivity([
                'entity_id' => $plan->id,
                'entity_type' => 'plans',
                'action_type' => 'update_plan',
                'title' => 'Plan #'.$plan->id.' '.$plan->name.' uppdaterad',
                'description' => 'En plan har uppdaterats.',
                'icon' => 'custom-cash',
                'route' => null, //'/dashboard/admin/plans/'.$plan->id,
                'metadata' => json_encode([
                    'plan_id' => $plan->id,
                    'old_values' => $oldValues,
                    'new_values' => $this->activityValues($plan)
                ])
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'plan' => $plan
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

    private function activityValues(Plan $plan): array
    {
        return [
            ...$plan->only(['name', 'description', 'price_month', 'price_annual']),
            'features' => $plan->features()
                ->orderBy('features.id')
                ->get(['features.id', 'features.name'])
                ->map(fn (Feature $feature) => $feature->only(['id', 'name']))
                ->all(),
        ];
    }

    public function destroy($id)
    {
        try {

            $plan = Plan::find($id);
        
            if (!$plan)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Planen hittades inte'
                ], 404);

            $oldValues = $plan->only(['name', 'description', 'price_month', 'price_annual', 'state_id']);

            Plan::deletePlan($id);

            SupplierActivity::createActivity([
                'entity_id' => $plan->id,
                'entity_type' => 'plans',
                'action_type' => 'delete_plan',
                'title' => 'Plan #'.$plan->id.' '.$plan->name.' borttagen',
                'description' => 'Planen har tagits bort.',
                'icon' => 'custom-cash',
                'route' => null, //'/dashboard/admin/plans/'.$plan->id,
                'metadata' => json_encode([
                    'plan_id' => $plan->id,
                    'old_values' => $oldValues
                ])
            ]);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'plan' => $plan
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

    public function updateState($id)
    {
        try {

            $plan = Plan::withTrashed()->find($id);
        
            if (!$plan)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Planen hittades inte'
                ], 404);
            
            if($plan->state_id === 1) {
                $plan->deleted_at = null;    
            }
            $plan->state_id = $plan->state_id === 1 ? 2 : 1;
            $plan->update();

            SupplierActivity::createActivity([
                'entity_id' => $plan->id,
                'entity_type' => 'plans',
                'action_type' => $plan->state_id === 2 ? 'activate_plan' : 'deactivate_plan',
                'title' => 'Plan #'.$plan->id.' '.$plan->name.' '.($plan->state_id === 2 ? 'aktiverad' : 'inaktiverad'),
                'description' => 'Planen har '.($plan->state_id === 2 ? 'aktiverats' : 'inaktiverats').'.',
                'icon' => 'custom-cash',
                'route' => null, //'/dashboard/admin/plans/'.$plan->id,
                'metadata' => json_encode([
                    'plan_id' => $plan->id
                ])
            ]);

            SupplierActivity::where('entity_id', $plan->id)
                ->where('entity_type', 'plans')
                ->update(['route' => null]);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'plan' => $plan
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
}
