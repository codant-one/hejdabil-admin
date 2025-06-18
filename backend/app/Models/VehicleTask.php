<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

use App\Models\TaskHistory;
use App\Models\TaskComment;

class VehicleTask extends Model
{
    use HasFactory;

    protected $guarded = [];
    
    /**** Relationship ****/
    public function vehicle(){
        return $this->belongsTo(Vehicle::class, 'vehicle_id', 'id');
    }

    public function user(){
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function comments(){
        return $this->hasMany(TaskComment::class, 'vehicle_task_id', 'id');
    }

    public function histories(){
        return $this->hasMany(TaskHistory::class, 'vehicle_task_id', 'id');
    }

    /**** Public methods ****/
    public static function createTask($request) {

        $task = self::create([
            'user_id' => Auth::user()->id,
            'vehicle_id' => $request->vehicle_id,
            'measure' => $request->measure,
            'cost' => $request->cost,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date === 'null' ? null : $request->end_date
        ]);

        TaskHistory::create([
            'user_id' => Auth::user()->id,
            'vehicle_task_id' => $task->id,
            'is_updated' => 1
        ]);
        
        return $task;
    }

    public static function updateTask($request, $task) {

        $task->update([ 
            'vehicle_id' => $request->vehicle_id,
            'measure' => $request->measure,
            'cost' => $request->cost,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date === 'null' ? null : $request->end_date
        ]);

        TaskHistory::create([
            'user_id' => Auth::user()->id,
            'vehicle_task_id' => $task->id
        ]);

        return $task;
    }

    public static function deleteTask($id) {
        self::deletedeleteTasks(array($id));
    }

    public static function deletedeleteTasks($ids) {
        foreach ($ids as $id) {
            $task = self::find($id);
            $task->delete();
        }
    }

    public static function sendComment($request) {
        TaskComment::create([
            'user_id' => Auth::user()->id,
            'vehicle_task_id' => $request->id,
            'comment' => $request->comment
        ]);
    }
}
