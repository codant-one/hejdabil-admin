<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use Carbon\Carbon;

use App\Models\PasswordReset;
use App\Models\User;

class PasswordResetController extends Controller
{
    
    /**
     * create reset password mail.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function forgot_password(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user)
            return response()->json([
                'success' => false,
                'message' => 'not_found',
                'errors' => 'Email not registered'
            ], 404);

        $passwordReset = PasswordReset::updateOrCreate(
            ['email' => strtolower($request->email)],
            ['token' => Str::random(60)]
        );
        
        $email = $user->email;
        $url = env('APP_DOMAIN').'/reset-password?token='.$passwordReset['token'].'&user='.$email;
        
        $info = [
            'subject' => 'Password change request',
            'buttonLink' =>  $url ?? null,
            'email' => 'emails.auth.forgot_pass_confirmation'
        ];     
        
        $responseMail = $this->sendMail($user->id, $info); 

        return response()->json([
            'success' => $responseMail['success'],
            'message' => 'forgot_password',
            'data' => [ "register_success" => $responseMail['message'] ]
        ], 200);
    }

    public function find($token)
    {
        $passwordReset = PasswordReset::where('token', $token)->first();
        
        if (!$passwordReset)
            return response()->json([
                'success' => false,
                'message' => 'not_found',
                'errors' => 'The password reset token is invalid'
            ], 404);
            
        if (Carbon::parse($passwordReset->updated_at)->addMinutes(720)->isPast()) {
            $passwordReset->delete();
            return response()->json([
                'success' => false,
                'message' => 'not_found',
                'errors' => 'The password reset token is invalid'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'result' => $passwordReset,
            'message' => 'token_success'
            
        ], 200);
    }

    public function change(Request $request) {
        if ($this->find($request->token)->status() != 200)
            return response()->json([
                'success' => false,
                'message' => 'not_found',
                'errors' => 'The token is invalid!'
            ], 404);

        $tokenValidated = json_decode($this->find($request->token)->content());
        $email = $tokenValidated->result->email;
        $user = User::where('email', $email)->first();

        if (!$user)
            return response()->json([
                'success' => false,
                'message' => 'not_found',
                'errors' => 'Email not registered'
            ], 404);

        $user->password = Hash::make($request->password);
        $user->token_2fa = null;
        $user->update();

        $info = [
            'subject' => 'Hi '.$user->name.'!. Your password has been updated.',
            'buttonLink' => env('APP_DOMAIN'),
            'email' => 'emails.auth.reset_password'
        ];     
        
        $responseMail = $this->sendMail($user->id, $info); 

        return response()->json([
            'success' => $responseMail['success'],
            'message' => 'reset_password',
            'data' => 'Password has been updated'
        ], 200);

    }

    private function sendMail($id, $info ){

        $user = User::find($id);
        $response = [];

        $data = [
            'title' => $info['title']?? null,
            'user' => $user->name . ' ' . $user->last_name,
            'text' => $info['text'] ?? null,
            'buttonLink' =>  $info['buttonLink'] ?? null,
            'buttonText' =>  $info['buttonText'] ?? null
        ];

        $clientEmail = $user->email;
        $subject = $info['subject'];
        
        try {
            \Mail::send($info['email'], $data, function ($message) use ($clientEmail, $subject) {
                    $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
                    $message->to($clientEmail)->subject($subject);
            });

            $response['success'] = true;
            $response['message'] = "Your request has been processed successfully.";
        } catch (\Exception $e){
            $response['success'] = false;
            $response['message'] = "An error occurred, the email could not be sent. ".$e;
        }        

        return $response;

    } 
}
