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
            'description' => $request->description  === 'null' ? null : $request->description,
            'start_date' => $request->start_date === 'null' ? null : $request->start_date,
            'end_date' => $request->end_date === 'null' ? null : $request->end_date,
            'is_cost' => $request->is_cost === 'true' ? 1 : 0
        ]);

        TaskHistory::create([
            'user_id' => Auth::user()->id,
            'vehicle_task_id' => $task->id,
            'is_created' => 1
        ]);
        
        return $task;
    }

    public static function updateTask($request, $task) {

        $task->update([ 
            'measure' => $request->measure,
            'cost' => $request->cost,
            'description' => $request->description  === 'null' ? null : $request->description,
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
        self::deleteTasks(array($id));
    }

    public static function deleteTasks($ids) {
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

    public static function updateComment($request, $id) {
        $comment = TaskComment::find($id);
        
        if ($comment) {
            $comment->update([
                'comment' => $request->comment
            ]);
        }
        
        return $comment;
    }

    public static function deleteComment($id) {
        $comment = TaskComment::find($id);
        
        if ($comment) {
            $comment->delete();
        }
        
        return true;
    }

    public static function updateType($task) {

        $task->update([ 
            'is_cost' => 1
        ]);

        return $task;
    }   
}
