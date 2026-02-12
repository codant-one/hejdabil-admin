<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Middlewares\PermissionMiddleware;
use App\Services\CacheService;

class PermissionController extends Controller
{
    public function __construct(){
        $this->middleware(PermissionMiddleware::class . ':view roles|administrator')->only(['index']);
    }

     /**
     * Display a listing of the resource.
     */
    public function all(){

        try {

            return response()->json([
                'success' => true,
                'data' => [
                    'permissions' => CacheService::getPermissions()
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
