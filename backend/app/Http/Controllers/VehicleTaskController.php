<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

use App\Models\SupplierActivity;
use App\Models\Vehicle;
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
            $vehicle = Vehicle::find($request->vehicle_id);
            $oldValues = $vehicle ? $this->getVehicleCostActivityValues($vehicle) : [];

            $task = VehicleTask::createTask($request);

            if ($vehicle) {
                $updatedVehicle = Vehicle::find($vehicle->id);

                if ($updatedVehicle) {
                    $newValues = $this->getVehicleCostActivityValues($updatedVehicle);
                    $this->createVehicleCostActivity($updatedVehicle, $oldValues, $newValues);
                }
            }

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

            $vehicle = Vehicle::find($task->vehicle_id);
            $oldValues = $vehicle ? $this->getVehicleCostActivityValues($vehicle) : [];

            $task->updateTask($request, $task); 

            if ($vehicle) {
                $updatedVehicle = Vehicle::find($vehicle->id);

                if ($updatedVehicle) {
                    $newValues = $this->getVehicleCostActivityValues($updatedVehicle);
                    $this->createVehicleCostActivity($updatedVehicle, $oldValues, $newValues);
                }
            }

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

            $vehicle = Vehicle::find($task->vehicle_id);
            $oldValues = $vehicle ? $this->getVehicleCostActivityValues($vehicle) : [];
            
            $task->deleteTask($id);

            if ($vehicle) {
                $updatedVehicle = Vehicle::find($vehicle->id);

                if ($updatedVehicle) {
                    $newValues = $this->getVehicleCostActivityValues($updatedVehicle);
                    $this->createVehicleCostActivity($updatedVehicle, $oldValues, $newValues);
                }
            }

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

    public function updateType($id)
    {
        try {
            $task = VehicleTask::find($id);
        
            if (!$task)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Uppgift hittades inte'
                ], 404);

            $vehicle = Vehicle::find($task->vehicle_id);
            $oldValues = $vehicle ? $this->getVehicleCostActivityValues($vehicle) : [];

            $task->updateType($task); 

            if ($vehicle) {
                $updatedVehicle = Vehicle::find($vehicle->id);

                if ($updatedVehicle) {
                    $newValues = $this->getVehicleCostActivityValues($updatedVehicle);
                    $this->createVehicleCostActivity($updatedVehicle, $oldValues, $newValues);
                }
            }

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

    private function getVehicleCostActivityValues(Vehicle $vehicle): array
    {
        $vehicle->load('tasks');

        return [
            'costs' => $vehicle->tasks
                ->filter(fn ($task) => (int) $task->is_cost === 1)
                ->sum(fn ($task) => (float) $task->cost),
        ];
    }

    private function createVehicleCostActivity(Vehicle $vehicle, array $oldValues, array $newValues): void
    {
        if (($oldValues['costs'] ?? null) === ($newValues['costs'] ?? null))
            return;

        SupplierActivity::createActivity([
            'entity_id' => $vehicle->id,
            'entity_type' => 'vehicles',
            'action_type' => 'update_vehicle',
            'title' => 'Fordon uppdaterat',
            'description' => 'Fordon ' . $vehicle->reg_num . ' har uppdaterats.',
            'icon' => 'custom-car',
            'route' => $this->resolveVehicleRoute($vehicle),
            'metadata' => json_encode([
                'vehicle_id' => $vehicle->id,
                'old_values' => $oldValues,
                'new_values' => $newValues,
            ])
        ]);
    }

    private function resolveVehicleRoute(Vehicle $vehicle): string
    {
        return (int) $vehicle->state_id === 12
            ? '/dashboard/admin/sold?vehicle_id=' . $vehicle->id
            : '/dashboard/admin/stock?vehicle_id=' . $vehicle->id;
    }
}
