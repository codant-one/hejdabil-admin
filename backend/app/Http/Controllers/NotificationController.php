<?php

namespace App\Http\Controllers;

use App\Http\Requests\NotificationRequest;
use App\Events\NotificationsWebsocketEvent;
use App\Events\UserNotificationEvent;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get notifications from the authenticated user
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $notifications = 
            Notification::where(function($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereNull('user_id'); // Notificaciones públicas
            })
            ->where('read', 0)
            ->orderBy('created_at', 'desc')
            ->limit(4)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notifications
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($id)
    {
        $user = Auth::user();
        
        $notification = 
            Notification::where('id', $id)
                ->where(function($query) use ($user) {
                    $query->where('user_id', $user->id)
                        ->orWhereNull('user_id');
                })
                ->firstOrFail();

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Meddelande markerat som läst'
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        
        Notification::where(function($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->orWhereNull('user_id');
        })
        ->where('read', false)
        ->update(['read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Alla meddelanden markerade som lästa'
        ]);
    }

    /**
     * Send a notification via WebSocket and save in DB
     * Can send to a specific user (private channel) or to all (public channel)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function send(NotificationRequest $request)
    {
        try {
            $userId = $request->input('user_id');
            $notification_id = $request->input('notification_id');
            $title = $request->input('title');
            $text = $request->input('text');
            $subtitle = $request->input('subtitle', '');
            $color = $request->input('color', 'primary');
            $icon = $request->input('icon', 'tabler-bell');
            $img = $request->input('img');
            $route = $request->input('route', '/dashboard/panel');

            // Guardar en base de datos
            $dbNotification = Notification::create([
                'user_id' => $userId,
                'notification_id' => $notification_id,
                'title' => $title,
                'subtitle' => $subtitle,
                'text' => $text,                
                'color' => $color,
                'icon' => $icon,
                'route' => $route,
                'read' => false,
            ]);

            // Preparar el mensaje de notificación para WebSocket
            $message = (object) [
                'id' => $dbNotification->id,
                'title' => $title,
                'subtitle' => $subtitle,
                'time' => now()->format('H:i:s'),
                'img' => $img,
                'color' => $color,
                'icon' => $icon,
                'text' => $text,
                'route' => $route,
                'read' => false,
            ];

            // Enviar via WebSocket
            if ($userId) {
                // Notificación privada
                $evento = new UserNotificationEvent($message, $userId);
                Event::dispatch($evento);
            } else {
                // Notificación pública                
                $evento = new NotificationsWebsocketEvent($message);
                Event::dispatch($evento);
            }

            return response()->json([
                'success' => true,
                'message' => 'Meddelande skickat korrekt',
                'data' => [
                    'id' => $dbNotification->id,
                    'title' => $title,
                    'text' => $text,
                    'time' => $message->time,
                    'user_id' => $userId ?? 'todos',
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fel vid sändning av meddelande',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
