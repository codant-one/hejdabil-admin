<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

use App\Models\VehicleTask;

class VehicleTaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {

            $task = VehicleTask::createTask($request);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'task' => VehicleTask::find($task->id)
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
    public function show(VehicleTask $task)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $task = VehicleTask::find($id);
        
            if (!$task)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Uppgift hittades inte'
                ], 404);

            $task->updateTask($request, $task); 

            return response()->json([
                'success' => true
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

            $task = VehicleTask::find($id);
        
            if (!$task)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Uppgift hittades inte'
                ], 404);
            
            $task->deleteTask($id);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'task' => $task
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

    public function comment(Request $request)
    {
        try {

            VehicleTask::sendComment($request);

            return response()->json([
                'success' => true
            ]);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error '.$ex->getMessage(),
                'exception' => $ex->getMessage()
            ], 500);
        }
    }

    public function updateComment(Request $request, $id)
    {
        try {

            VehicleTask::updateComment($request, $id);

            return response()->json([
                'success' => true
            ]);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error '.$ex->getMessage(),
                'exception' => $ex->getMessage()
            ], 500);
        }
    }

    public function deleteComment(Request $request, $id)
    {
        try {

            VehicleTask::deleteComment($id);

            return response()->json([
                'success' => true
            ]);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error '.$ex->getMessage(),
                'exception' => $ex->getMessage()
            ], 500);
        }
    }
}
