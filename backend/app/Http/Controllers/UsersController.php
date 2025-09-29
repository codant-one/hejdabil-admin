<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use Spatie\Permission\Middlewares\PermissionMiddleware;

use App\Http\Requests\ProfileRequest;
use App\Http\Requests\UserRequest;

use App\Models\User;
use App\Models\UserDetails;
use App\Models\UserRegisterToken;
use App\Models\Supplier;

class UsersController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt');
        $this->middleware(PermissionMiddleware::class . ':view users|administrator')->only(['index']);
        $this->middleware(PermissionMiddleware::class . ':create users|administrator')->only(['store']);
        $this->middleware(PermissionMiddleware::class . ':edit users|administrator')->only(['update','updatePasswordUser']);
        $this->middleware(PermissionMiddleware::class . ':delete users|administrator')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            
            $limit = $request->has('limit') ? $request->limit : 10;;

            $query = User::with(['roles', 'userDetail'])
                         ->whereHas('roles', function ($query) {
                            $query->where('name', 'SuperAdmin')
                                ->orWhere('name', 'Administrator')
                                ->orWhere('name', 'Operator');
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
    public function store(UserRequest $request): JsonResponse
    {
        try{
            
            $user = User::createUser($request);

            UserRegisterToken::updateOrCreate(
                ['user_id' => $user->id],
                ['token' => Str::random(60)]
            );

            $email = $user->email;
            $subject = 'Välkommen till HejdåBil';
    
            $data = [
                'title' => 'Konto skapat framgångsrikt!!!',
                'user' => $user->name . ' ' . $user->last_name,
                'email'=> $email,
                'password' => $request->password,
                'buttonLink' => env('APP_DOMAIN'),
                'text-url' => 'Administrative panel'
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
    public function update(UserRequest $request, $id): JsonResponse
    {
        try {

            $user = User::with('roles', 'userDetail')->find($id);
        
            if (!$user)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Användaren hittades inte'
                ], 404);

            $user->updateUser($request, $user); 

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
     * Remove the specified resource from storage.
     */
    public function destroy($id): JsonResponse
    {
        try {

            $user = User::with('roles', 'userDetail')->find($id);
        
            if (!$user)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Användaren hittades inte'
                ], 404);
            
            $user->deleteUser($id);

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
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(ProfileRequest $request): JsonResponse
    {

        try {

            $user = Auth::user()->load(['userDetail']);
            $user->updateProfile($request, $user);

            if ($request->hasFile('image')) {
                $image = $request->file('image');

                $path = 'avatars/';

                $file_data = uploadFile($image, $path, $user->avatar);

                $user->avatar = $file_data['filePath'];
                $user->update();
            } 

            $userData = getUserData($user->load(['userDetail']));

            return response()->json([
                'success' => true,
                'data' => [ 
                    'user_data' => $userData
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $validate = Validator::make($request->all(), [
            'password' => 'required'
        ]);
    
        if ($validate->fails()) {
            return response()->json([
                'success' => false,
                'feedback' => 'params_validation_failed',
                'message' => $validate->errors()
            ], 400);
        }

        try {

            $user = Auth::user()->load(['userDetail']);
            $user->password = Hash::make($request->password);
            $user->save();

            return response()->json([
                'success' => true,
                'data' => [ 
                    'user_data' => getUserData($user)
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePasswordUser(Request $request, string $id): JsonResponse
    {
        $validate = Validator::make($request->all(), [
            'password' => 'required'
        ]);

        if ($validate->fails()) {
            return response()->json([
                'success' => false,
                'feedback' => 'params_validation_failed',
                'message' => $validate->errors()
            ], 400);
        }

        try {

            $user = User::with(['userDetail'])->find($id);
        
            if (!$user)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Användaren hittades inte'
                ], 404);

            $user->password = Hash::make($request->password);
            $user->save();

            return response()->json([
                'success' => true,
                'data' => [ 
                    'user_data' => getUserData($user)
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

    public function getOnline(Request $request): JsonResponse
    {
        try{
            
            $users = User::getOnline($request);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'users' => $users
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

    public function getProfile(): JsonResponse
    {

        try {

            $user = User::find(Auth::user()->id);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'user_data' => $user
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

    public function updateCompany(Request $request): JsonResponse
    {

        try {

            $user = Auth::user()->load(['userDetail']);
            $user_details = UserDetails::where('user_id', $user->id)->first();
            $user_details->updateOrCreateUser($request, $user);

            if ($request->hasFile('logo')) {
                $image = $request->file('logo');

                $path = 'logos/';

                $file_data = uploadFile($image, $path, $user_details->logo);

                $user_details->logo = $file_data['filePath'];
                $user_details->update();
            }
            
            $userData = getUserData($user->load(['userDetail']));

            return response()->json([
                'success' => true,
                'data' => [ 
                    'user_data' => $userData
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

    public function updateLogo(Request $request): JsonResponse
    {

        try {

            $user = Auth::user()->load(['userDetail']);
            $user_details = UserDetails::where('user_id', $user->id)->first();

            if ($request->hasFile('logo')) {
                $image = $request->file('logo');

                $path = 'logos/';

                $file_data = uploadFile($image, $path, $user_details->logo);

                $user_details->logo = $file_data['filePath'];
                $user_details->update();
            } else {
                $user_details->logo = null;
                $user_details->update();
            }

            $userData = getUserData($user->load(['userDetail']));

            return response()->json([
                'success' => true,
                'data' => [ 
                    'user_data' => $userData
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
