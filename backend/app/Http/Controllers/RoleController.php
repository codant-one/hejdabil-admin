<?php

namespace App\Http\Controllers;

use Spatie\Permission\Middlewares\PermissionMiddleware;

use App\Http\Requests\RoleRequest;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use App\Models\Role;
use App\Services\CacheService;
use App\Models\SupplierActivity;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt');
        $this->middleware(PermissionMiddleware::class . ':view roles|administrator')->only(['index']);
        $this->middleware(PermissionMiddleware::class . ':create roles|administrator')->only(['store']);
        $this->middleware(PermissionMiddleware::class . ':view roles|edit roles|administrator')->only(['show']);
        $this->middleware(PermissionMiddleware::class . ':edit roles|administrator')->only(['update']);
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

    public function show($id): JsonResponse
    {
        try {
            $role = Role::with('permissions')->find($id);

            if (!$role)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Rollen hittades inte'
                ], 404);

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
     * Store a newly created resource in storage.
     */
    public function store(RoleRequest $request): JsonResponse
    {
        try {

            $role = Role::createRole($request);

            SupplierActivity::createActivity([
                'entity_id' => $role->id,
                'entity_type' => 'roles',
                'action_type' => 'create_role',
                'title' => 'Roll #'.$role->id.' '.$role->name.' tillagd',
                'description' => 'En ny roll har lagts till.',
                'icon' => 'custom-profile',
                'route' => '/dashboard/admin/roles/'.$role->id,
                'metadata' => json_encode([
                    'role_id' => $role->id,
                    'new_values' => $request->only([
                        'name', 'permissions'
                    ])
                ])
            ]);

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

            $role = Role::with('permissions')->find($id);

            if (!$role)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Rollen hittades inte'
                ], 404);

            $oldValues = $role->only(['name']);
            $oldValues['permissions'] = $role->permissions->pluck('name')->toArray();
            

            $role->updateRole($request, $role); 

            SupplierActivity::createActivity([
                'entity_id' => $role->id,
                'entity_type' => 'roles',
                'action_type' => 'update_role',
                'title' => 'Roll #'.$role->id.' '.$role->name.' uppdaterad',
                'description' => 'En roll har uppdaterats.',
                'icon' => 'custom-profile',
                'route' => '/dashboard/admin/roles/'.$role->id,
                'metadata' => json_encode([
                    'role_id' => $role->id,
                    'old_values' => $oldValues,
                    'new_values' => $request->only([
                        'name', 'permissions'
                    ])
                ])
            ]);

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

            $role = Role::with('permissions')->find($id);
        
            if (!$role)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Rollen hittades inte'
                ], 404);

            $oldValues = $role->only(['name']);
            $oldValues['permissions'] = $role->permissions->pluck('name')->toArray();

            $role->deleteRole($id);

            SupplierActivity::createActivity([
                'entity_id' => $role->id,
                'entity_type' => 'roles',
                'action_type' => 'delete_role',
                'title' => 'Roll #'.$role->id.' '.$role->name.' borttagen',
                'description' => 'En roll har tagits bort.',
                'icon' => 'custom-profile',
                'route' => '/dashboard/admin/roles/'.$role->id,
                'metadata' => json_encode([
                    'role_id' => $role->id,
                    'old_values' => $oldValues
                ])
            ]);

            SupplierActivity::where('entity_id', $role->id)
                ->where('entity_type', 'roles')
                ->update(['route' => null]);

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
