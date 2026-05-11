<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use App\Services\TwilioSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SmsTestController extends Controller
{
    public function sendTest(Request $request, TwilioSmsService $twilioSmsService)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => ['required', 'string', 'regex:/^\+[1-9]\d{7,14}$/'],
            'message' => ['required', 'string', 'max:1600'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $twilioSmsService->sendMessage(
            $request->input('phone_number'),
            $request->input('message')
        );

        if ($result === true) {
            return response()->json([
                'status' => 'success',
                'message' => 'SMS enviado correctamente.',
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => $result,
        ], 500);
    }
}