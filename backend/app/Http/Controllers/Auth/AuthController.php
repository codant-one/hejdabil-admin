<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

use Carbon\Carbon;

use App\Models\User;
use App\Models\UserDetails;
use App\Models\UserRegisterToken;
use App\Models\Supplier;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('jwt', ['except' => 
            ['login', 'register', 'find', 'completed', 'sendInfo']
        ]);
    }

    /**
     * Store a newly created resource in storage
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'missing_params',
                'errors' => implode (', ', $validator->errors()->all())
            ], 400);
        }

        $credentials = request(['email', 'password']);

        $expired = now()->addHours(24);
            
        if (!$token = Auth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'invalid_credentials',
                'errors' => 'Ogiltigt användarnamn eller lösenord'
            ], 400);
        }

        $user = Auth::user();
        $user->online = Carbon::now();
        $user->save();

        if (env('APP_DEBUG') || ($user->is_2fa === 0)) {
            return response()->json([
                'success' => true,
                'message' => 'login_success',
                'data' => $this->respondWithToken($token)
            ], 200);
        }

        if (empty($user->token_2fa)) {
            $google2fa = app('pragmarx.google2fa');
            $token2FA = $google2fa->generateSecretKey();

            $user->token_2fa = $token2FA;
            $user->update();

            $qr = $google2fa->getQRCodeUrl(
                config('app.name'),
                $user->email,
                $token2FA
            );

            $data = [
                'qr' => $qr,
                'token' => $token2FA
            ];

            return response()->json([
                'success' => true,
                'message' => '2fa-generate',
                'data' => array_merge($data, $this->respondWithToken($token))
            ], 200);

        } else {

            $data = [
                'token' => $user->token_2fa
            ];

            return response()->json([
                'success' => true,
                'message' => '2fa',
                'data' => array_merge($data, $this->respondWithToken($token))
            ], 200);
        }
    }

    public function validate_double_factor_auth(Request $request)
    {
        $user = auth()->user();
        $google2fa = app('pragmarx.google2fa');

        if ($google2fa->verifyKey($user->token_2fa, $request->token_2fa)) {
            session()->put('2fa', '1');

            if($request->panel) {
                $user->is_2fa =  ($user->is_2fa === 0) ? 1 : 0;
                $user->update();
            }

            return response()->json([
                'success' => true,
                'message' => 'login_success'
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'invalid_code',
            'errors' => 'Felaktig verifieringskod'
        ], 400);
    }

    public function generateQR()
    {
        $user = Auth::user();
        $google2fa = app('pragmarx.google2fa');
        $token2FA = '';

        if (empty($user->token_2fa)) {
            $token2FA = $google2fa->generateSecretKey();

            $user->token_2fa = $token2FA;
            $user->update();
        }

        $qr = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            empty($user->token_2fa) ? $token2FA : $user->token_2fa
        );

        $data = [
            'qr' => $qr,
            'is_2fa' => ($user->is_2fa === 0) ? false : true,
            'token' => empty($user->token_2fa) ? $token2FA : $user->token_2fa
        ];

        return response()->json([
            'success' => true,
            'message' => 'generate-qr',
            'data' => $data
        ], 200);

        return response()->json([
            'success' => false,
            'message' => 'invalid_code',
            'errors' => 'Felaktig verifieringskod'
        ], 400);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        $hash = $request->hash;

        $user = Auth::user();
        $user->online = Carbon::now();
        $user->save();

        if($user->password === $hash){

            $permissions = getPermissionsByRole(Auth::user());
            $userData = getUserData(Auth::user()->load(['userDetail', 'supplier.billings']));

            return response()->json([
                'success' => true,
                'data' => [ 
                    'user_data' => $userData,
                    'userAbilities' => $permissions
                ]
            ], 200);

        } else {
            return response()->json([
                'success' => false,
                'message' => 'params_validation_failed',
                'error' => 'Data stämmer inte överens'
            ], 400);
        }

    }

    /**
     * Log the user out (Invalidate the token).
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(): JsonResponse
    {
        Auth::logout();

        return response()->json([
            'success' => true,
            'message' => 'Logga ut framgångsrikt'
        ], 200);
    }

    public function find($token)
    {
        
        try {

            $emailConfirm = UserRegisterToken::where('token', $token)->first();

            if (!$emailConfirm)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Ogiltig token'
                ], 404);

            if (Carbon::parse($emailConfirm->updated_at)->addMinutes(720)->isPast()) {
                $emailConfirm->delete();

                return response()->json([
                    'success' => false,
                    'feedback' => 'error_token',
                    'message' => 'Utgången Token'
                ], 404);
                
            }

            return response()->json([
                'success' => true,
                'message' => 'E-postbekräftelse framgångsrik',
                'data' => [ 
                    'token' => $token
                ]
            ], 200);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error '.$ex->getMessage(),
                'exception' => $ex->getMessage()
            ], 500);
        }

    }

    public function completed(Request $request)
    {
        try {
        
            $emailConfirm = UserRegisterToken::where('token', $request->token)->first();
            $user = User::where('id', $emailConfirm->user_id)->first();

            if (!$user)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Oregistrerad användare'
                ], 404);

            if ($user->email_verified_at == null) {
                $user->email_verified_at = now();
                $user->update();
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Din begäran har behandlats framgångsrikt. E-post verifierad. Vänligen logga in.',
            ], 200);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error '.$ex->getMessage(),
                'exception' => $ex->getMessage()
            ], 500);
        }
    } 

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        $permissions = getPermissionsByRole(Auth::user());
        $userData = getUserData(Auth::user()->load(['userDetail', 'supplier.billings']));

        return [
            'accessToken' => $token,
            'token_type' => 'bearer',
            'user_data' => $userData,
            'userAbilities' => $permissions
        ];
    }

    public function companyDetail()
    {
        try {
            
            $supplier = 
                Supplier::where('user_id', Auth::user()->id)
                        ->first();
        
            return response()->json([
                'success' => true,
                'message' => 'company',
                'data' => [
                    'supplier' => $supplier
                ]
            ], 200);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error '.$ex->getMessage(),
                'exception' => $ex->getMessage()
            ], 500);
        }
    }

    private function sendMail($info, $id = 1){

        $user = User::find($id);
        
        $data = [
            'name' => $info['name'] ?? null,
            'nit' => $info['nit'] ?? null,
            'email' => $info['email_contact'] ?? null,
            'phone' => $info['phone'] ?? null,
            'title' => $info['title'] ?? null,
            'user' => $user->name . ' ' . $user->last_name,
            'text' => $info['text'] ?? null,
            'buttonLink' =>  $info['buttonLink'] ?? null,
            'buttonText' =>  $info['buttonText'] ?? null
        ];

        $email = ($id === 1) ? env('MAIL_TO_CONTACT') : $user->email;
        $subject = $info['subject'];
        
        try {
            \Mail::send($info['email'], $data, function ($message) use ($email, $subject) {
                    $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
                    $message->to($email)->subject($subject);
            });

            return "Din begäran har behandlats framgångsrikt. E-post verifierad. Vänligen logga in.";
        } catch (\Exception $e){
            return "Fel vid sändning av e-post. ".$e;
        }        

        return "";

    } 
}
