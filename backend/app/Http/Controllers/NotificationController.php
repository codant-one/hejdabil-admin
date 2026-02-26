<?php

namespace App\Http\Controllers;

use App\Http\Requests\NotificationRequest;
use App\Events\NotificationsWebsocketEvent;
use App\Events\UserNotificationEvent;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use App\Models\Supplier;

class NotificationController extends Controller
{
     /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $limit = $request->has('limit') ? $request->limit : 10;
        
            $query = Notification::with([
                            'user:id,name,last_name,email,avatar',
                            'user.userDetail:user_id,logo'
                        ])
                         ->applyFilters(
                                $request->only([
                                    'search',
                                    'orderByField',
                                    'orderBy',
                                    'user_id'
                                ])
                            );

            if ($limit == -1) {
                $allNotifications = $query->get();
                $notifications = new \Illuminate\Pagination\LengthAwarePaginator(
                    $allNotifications,
                    $allNotifications->count(),
                    $allNotifications->count(),
                    1
                );
            } else {
                $notifications = $query->paginate($limit);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'notifications' => $notifications,
                    'notificationsTotalCount' => $notifications->total()
                ]
            ]);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
              'success' => false,
              'message' => 'database_error',
              'exception' => $ex->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(NotificationRequest $request)
    {
        //
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
    public function update(NotificationRequest $request, $id): JsonResponse
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {

            $notification = Notification::with(['user'])->find($id);
        
            if (!$notification)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => ' Anmälan hittades inte'
                ], 404);
            
            $notification->deleteNotification($id);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'notification' => $notification
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

    /**
     * Get notifications from the authenticated user
     */
    public function listRecent(Request $request)
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
     * Delete all notifications for a specific user
     */
    public function clearAll(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'validation_error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $authUser = Auth::user();
        $targetUserId = (int) $request->input('user_id');

        if (!$authUser || (int) $authUser->id !== $targetUserId) {
            return response()->json([
                'success' => false,
                'message' => 'unauthorized',
            ], 403);
        }

        $deletedRows = Notification::where('user_id', $targetUserId)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Alla meddelanden raderade',
            'data' => [
                'deleted_count' => $deletedRows,
            ]
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

            $supplier = Supplier::where('user_id', $request->input('user_id'))->first();
            $supplierId = null;

            if($supplier) {
                $supplierId = $supplier->boss_id === null ? $supplier->id : $supplier->boss_id;
            }

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
                'supplier_id' => $supplierId,
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
