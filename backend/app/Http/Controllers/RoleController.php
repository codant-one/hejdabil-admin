<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleRequest;

use App\Models\Role;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Middlewares\PermissionMiddleware;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt');
        $this->middleware(PermissionMiddleware::class . ':view roles|administrator')->only(['index']);
        $this->middleware(PermissionMiddleware::class . ':create roles|administrator')->only(['store']);
        $this->middleware(PermissionMiddleware::class . ':edir roles|administrator')->only(['update']);
        $this->middleware(PermissionMiddleware::class . ':delete roles|administrator')->only(['destroy']);
        $this->middleware(PermissionMiddleware::class . ':view users|administrator')->only(['all']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $limit = $request->has('limit') ? $request->limit : 10;

            $query = Role::with('permissions')
                         ->whereNotIn('id', [1, 5])
                         ->applyFilters(
                            $request->only([
                                'search',
                                'orderByField',
                                'orderBy'
                            ])
                         );

            $count = $query->applyFilters(
                        $request->only([
                            'search',
                            'orderByField',
                            'orderBy'
                        ])
                    )->count();

            $roles = ($limit == -1) ? $query->paginate($query->count()) : $query->paginate($limit);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'roles' => $roles,
                    'rolesTotalCount' => $count
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
     * Store a newly created resource in storage.
     */
    public function store(RoleRequest $request): JsonResponse
    {
        try {

            $role = Role::createRole($request);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'role' => $role
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
     * Update the specified resource in storage.
     */
    public function update(RoleRequest $request, $id): JsonResponse
    {
        try {

            $role = Role::find($id);

            if (!$role)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Rollen hittades inte'
                ], 404);

            $role->updateRole($request, $role); 

            return response()->json([
                'success' => true,
                'data' => [ 
                    'role' => $role
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
     * Remove the specified resource from storage.
     */
    public function destroy($id): JsonResponse
    {
        try {

            $role = Role::find($id);
        
            if (!$role)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Rollen hittades inte'
                ], 404);

            $role->deleteRole($id);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'role' => $role
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

    public function all(){

        try {

            return response()->json([
                'success' => true,
                'data' => [
                    'roles' => CacheService::getRoles()
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
