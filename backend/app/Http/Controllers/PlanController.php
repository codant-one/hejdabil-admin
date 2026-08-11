<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlanRequest;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

use Spatie\Permission\Middlewares\PermissionMiddleware;

use App\Models\Plan;
use App\Models\Feature;

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

            $plan = Plan::updatePlan($request, $plan);

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

            Plan::deletePlan($id);

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
