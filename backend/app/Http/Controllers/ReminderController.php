<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReminderRequest;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use Spatie\Permission\Middlewares\PermissionMiddleware;

use App\Models\Reminder;

class ReminderController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ReminderRequest $request)
    {
        try {

            $reminder = Reminder::createReminder($request);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'reminder' => Reminder::with(['user'])->find($reminder->id)
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
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ReminderRequest $request, $id): JsonResponse
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {

            $reminder = Reminder::with(['user'])->find($id);
        
            if (!$reminder)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Påminnelse hittades inte'
                ], 404);
            
            $reminder->deleteReminder($id);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'reminder' => $reminder
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

    public function updateState(Request $request, $id): JsonResponse
    {
        try {

            $reminder = Reminder::with(['user'])->find($id);

            if (!$reminder)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Påminnelse hittades inte'
                ], 404);

            if ((int) $reminder->user_id !== (int) Auth::id()) {
                return response()->json([
                    'success' => false,
                    'feedback' => 'forbidden',
                    'message' => 'Du har inte behörighet att uppdatera denna påminnelse'
                ], 403);
            }

            $reminder = Reminder::updateStateReminder($reminder, (int) $request->is_done);

            return response()->json([
                'success' => true,
                'data' => [
                    'reminder' => Reminder::with(['user'])->find($reminder->id)
                ]
            ], 200);

        } catch(\Illuminate\Validation\ValidationException $ex) {
            return response()->json([
                'success' => false,
                'feedback' => 'params_validation_failed',
                'message' => implode(', ', $ex->validator->errors()->all())
            ], 400);
        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error',
                'exception' => $ex->getMessage()
            ], 500);
        }
    }
}
