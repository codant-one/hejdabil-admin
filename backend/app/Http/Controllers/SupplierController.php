<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Requests\SupplierRequest;
use App\Http\Requests\UserRequest;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Str;

use Spatie\Permission\Middlewares\PermissionMiddleware;

use App\Models\User;
use App\Models\UserRegisterToken;
use App\Models\Supplier;
use App\Models\UserDetails;

class SupplierController extends Controller
{
    public function __construct()
    {
        $this->middleware(PermissionMiddleware::class . ':view suppliers|administrator')->only(['index']);
        $this->middleware(PermissionMiddleware::class . ':create suppliers|administrator')->only(['store']);
        $this->middleware(PermissionMiddleware::class . ':edit suppliers|administrator')->only(['update']);
        $this->middleware(PermissionMiddleware::class . ':delete suppliers|administrator')->only(['destroy']);
    }

    
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $limit = $request->has('limit') ? $request->limit : 10;
        
            $query = Supplier::with([
                        'user.userDetail',
                        'creator.userDetail',
                        'state'
                    ])
                    ->withTrashed()
                    ->clientsCount()
                    ->whereNull('boss_id')
                    ->applyFilters(
                    $request->only([
                        'search',
                        'orderByField',
                        'orderBy',
                        'state_id'
                    ])
                );

            $count = $query->count();

            $suppliers = ($limit == -1) ? $query->paginate($query->count()) : $query->paginate($limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'suppliers' => $suppliers,
                    'suppliersTotalCount' => $count
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
    public function store(SupplierRequest $request)
    {
        try {

            $password = Str::random(8);
            $request->merge(['password' => $password]);

            $supplier = Supplier::createSupplier($request);

            UserRegisterToken::updateOrCreate(
                ['user_id' => $supplier->user_id],
                ['token' => Str::random(60)]
            );

            $email = $supplier->user->email;
            $subject = 'Välkommen till Billogg';
            
            $data = [
                'title' => 'Konto skapat framgångsrikt!!!',
                'user' => $supplier->user->name . ' ' . $supplier->user->last_name,
                'email'=> $email,
                'password' => $password,
                'url'=> env("APP_DOMAIN").'/login',
                'text-url'=>'Administrative panel'
            ];
            
            try {
                \Mail::send(
                    'emails.auth.client_created'
                    , ['data' => $data]
                    , function ($message) use ($email, $subject) {
                        $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
                        $message->to($email)->subject($subject);
                });

                $message = 'send_email';
                $responseMail = 'E-post skickad till leverantör framgångsrikt.';
            } catch (\Exception $e){
                $message = 'error';
                $responseMail = $e->getMessage();
            } 

            return response()->json([
                'success' => true,
                'email_response' => $responseMail,
                'data' => [ 
                    'supplier' => Supplier::with(['user.userDetail'])->find($supplier->id)
                ]
            ]);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error '.$ex->getMessage(),
                'exception' => $ex->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {

            $supplier = Supplier::with(['user.userDetail'])
                                ->withTrashed()
                                ->clientsCount()
                                ->find($id);

            if (!$supplier)
                return response()->json([
                    'sucess' => false,
                    'feedback' => 'not_found',
                    'message' => 'Leverantören hittades inte'
                ], 404);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'supplier' => $supplier
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
     * Update the specified resource in storage.
     */
    public function update(SupplierRequest $request, $id): JsonResponse
    {
        try {

            $supplier = Supplier::with(['user.userDetail', 'creator.userDetail'])->find($id);
        
            if (!$supplier)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Leverantören hittades inte'
                ], 404);

            $supplier->updateSupplier($request, $supplier); 

            return response()->json([
                'success' => true,
                'data' => [ 
                    'supplier' => $supplier
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
    public function destroy($id)
    {
        try {

            $supplier = Supplier::find($id);
        
            if (!$supplier)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Leverantören hittades inte'
                ], 404);
            
            $supplier->deleteSupplier($id);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'supplier' => $supplier
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

    public function activate($id)
    {
        try {

            $supplier = Supplier::onlyTrashed()->where('id', $id)->first();
        
            if (!$supplier)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Leverantören hittades inte'
                ], 404);
            
            $supplier->activateSupplier($id);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'supplier' => $supplier
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

    public function swish(Request $request, $id)
    {
        try {

            $supplier = Supplier::where('id', $id)->first();
        
            if (!$supplier)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Leverantören hittades inte'
                ], 404);
            
            $supplier->swish($request, $id);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'supplier' => $supplier
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

    public function masterPassword(Request $request, $id)
    {
        try {
            $supplier = Supplier::where('id', $id)->first();
        
            if (!$supplier)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Leverantören hittades inte'
                ], 404);
            
            $supplier->masterPassword($request, $id);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'supplier' => $supplier
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

    public function getMasterPassword($id)
    {
        try {
            $supplier = Supplier::where('id', $id)->first();
        
            if (!$supplier)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Leverantören hittades inte'
                ], 404);
            
            return response()->json([
                'success' => true,
                'data' => [ 
                    'master_password' => $supplier->master_password
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

    public function users(Request $request): JsonResponse
    {
        try {
            $limit = $request->has('limit') ? $request->limit : 10;

            $query = Supplier::with(['user.roles.permissions','user.permissions', 'user.userDetail'])
                        //  ->whereHas('user.roles', function ($query) {
                        //     $query->where('name', 'User');
                        //  })
                         ->when(Auth::user()->getRoleNames()[0] === 'Supplier', function ($query){
                            $query->where('boss_id', Auth::user()->supplier->id);
                         })
                         ->applyFilters(
                            $request->only([
                                'search',
                                'orderByField',
                                'orderBy'
                            ])
                        );

            $count = $query->count();

            $users = ($limit == -1) ? $query->paginate($query->count()) : $query->paginate($limit);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'users' => $users,
                    'usersTotalCount' => $count
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
     *
     * Store a newly created resource in storage.
     */
    public function addRelatedUser(UserRequest $request): JsonResponse
    {
        try{
            
            // $user = User::createUser($request);

            // $password = Str::random(8);
            // $request->merge(['password' => $password]);

            $order_id = Supplier::where('boss_id', Auth::user()->supplier->id)
                                ->max('order_id');

            $request->merge(['boss_id' => Auth::user()->supplier->id]);
            $request->merge(['order_id' => $order_id + 1]);
            $request->merge(['company' => ""]);
            $request->merge(['organization_number' => ""]);
            $request->merge(['address' => ""]);
            $request->merge(['phone' => ""]);
            $request->merge(['street' => ""]);
            $request->merge(['postal_code' => ""]);
            $request->merge(['bank' => ""]);
            $request->merge(['account_number' => ""]);

            $supplier = Supplier::createSupplier($request);

            UserRegisterToken::updateOrCreate(
                ['user_id' => $supplier->user_id],
                ['token' => Str::random(60)]
            );

            $user = User::find($supplier->user_id);
            $user->syncPermissions($request->permissions);
            $user->givePermissionTo('view dashboard');

            $email = $user->email;
            $subject = 'Välkommen till Billogg';
    
            $data = [
                'title' => 'Konto skapat framgångsrikt!!!',
                'user' => $user->name . ' ' . $user->last_name,
                'email'=> $email,
                'password' => $request->password,
                'buttonLink' => env('APP_DOMAIN'),
                'text-url'=>'Administrative panel'
            ];
    
            try {
                \Mail::send(
                    'emails.auth.user_created'
                    , ['data' => $data]
                    , function ($message) use ($email, $subject) {
                        $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
                        $message->to($email)->subject($subject);
                });
            } catch (\Exception $e) {    
                Log::info("Failed to send email to user ". $e);
            } 

            return response()->json([
                'success' => true,
                'data' => [ 
                    'user' => Supplier::with(['user.roles', 'user.userDetail'])->find($supplier->id)
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
    public function deleteRelatedUser($id): JsonResponse
    {
        try {

            $user = User::with('roles', 'userDetail')->find($id);
        
            if (!$user)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found ' . $id,
                    'message' => 'Användaren hittades inte'
                ], 404);

            $supplier = Supplier::where('user_id', $user->id)->first();
        
            if (!$supplier)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Leverantören hittades inte ' . $user->id
                ], 404);
            
            $supplier->deleteSupplier($supplier->id);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'user' => $user
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
    public function updateRelatedUser(Request $request, $id): JsonResponse
    {
        try {

            $user = User::with('roles', 'userDetail')->find($id);
        
            if (!$user)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Användaren hittades inte'
                ], 404);

            
            $request->merge(['roles' => [0 => "User"] ]);

            $user->updateUser($request, $user); 
            $user->syncPermissions($request->permissions);
            $user->givePermissionTo('view dashboard');

            return response()->json([
                'success' => true,
                'data' => [ 
                    'user' => $user
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
    public function permissionsRelatedUser(Request $request, $id): JsonResponse
    {
        try {

            $user = User::find($id);
        
            if (!$user)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Användaren hittades inte'
                ], 404);

            $user->syncPermissions($request->permissions);
            $user->givePermissionTo('view dashboard');

            return response()->json([
                'success' => true,
                'data' => [ 
                    'user' => $user
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
