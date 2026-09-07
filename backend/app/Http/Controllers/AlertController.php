<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use App\Models\Supplier;
use App\Models\Alert;
use App\Models\Setting;
use App\Models\User;

class AlertController extends Controller
{
    private const DEFAULT_NOTIFY_VIA_EMAIL = false;

     /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $limit = $request->has('limit') ? $request->limit : 10;
        
            $query = Alert::with([
                            'user' => fn($u) => $u->select('id', 'name', 'last_name', 'email', 'avatar', 'deleted_at')->withTrashed(),
                            'user.userDetail:user_id,avatar_id,logo'
                        ])
                         ->applyFilters(
                                $request->only([
                                    'search',
                                    'orderByField',
                                    'orderBy',
                                    'user_id'
                                ])
                            );

            if ($limit == -1) {
                $allAlerts = $query->get();
                $alerts = new \Illuminate\Pagination\LengthAwarePaginator(
                    $allAlerts,
                    $allAlerts->count(),
                    max($allAlerts->count(), 1),
                    1
                );
            } else {
                $alerts = $query->paginate($limit);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'alerts' => $alerts,
                    'alertsTotalCount' => $alerts->total()
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
}
