<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;

use App\Models\VehicleTask;
use App\Models\Notification;
use App\Events\UserNotificationEvent;

class SendNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications to users';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        self::overdueTasks();

        return 0;
    }

    private function overdueTasks() {

        $tasks = 
            VehicleTask::with(['vehicle', 'user'])
                ->where('is_cost', 0)
                ->whereNotNull('end_date')
                ->whereDate('end_date', '<=', now())
                ->whereHas('vehicle', function($query) {
                    $query->where('state_id', '!=', 12);
                })
                ->get();
        
        foreach($tasks as $task){
            
            // Prepare data for notification
            $vehicle = $task->vehicle;
            $regNum = $vehicle ? $vehicle->reg_num : 'N/A';
            
            $title = 'Åtgärd försenad';
            $subtitle = $regNum;
            $text = 'Åtgärden "' . $task->measure . '" skulle ha slutförts ' . $task->end_date;
            $color = 'error';
            $icon = 'custom-atgarder-2';
            $route = '/dashboard/admin/stock/edit/' . $task->vehicle_id . '#tab-tasks';
            
            // Create notification directly in database
            try {
                $dbNotification = Notification::create([
                    'user_id' => $task->user_id,
                    'notification_id' => $task->id,
                    'title' => $title,
                    'subtitle' => $subtitle,
                    'text' => $text,                
                    'color' => $color,
                    'icon' => $icon,
                    'route' => $route,
                    'read' => false,
                ]);
                
                // Prepare message for WebSocket
                $message = (object) [
                    'id' => $dbNotification->id,
                    'title' => $title,
                    'subtitle' => $subtitle,
                    'time' => now()->format('H:i:s'),
                    'img' => null,
                    'color' => $color,
                    'icon' => $icon,
                    'text' => $text,
                    'route' => $route,
                    'read' => false,
                ];
                
                // Send WebSocket event
                if ($task->user_id) {
                    $evento = new UserNotificationEvent($message, $task->user_id);
                    Event::dispatch($evento);
                }
                
                $this->info('Notification sent successfully for: ' . $task->measure);
            } catch (\Exception $e) {
                $this->error('Error creating notification: ' . $e->getMessage());
            }
        }
        
        $this->info('Total overdue tasks: ' . $tasks->count());
    }

}
