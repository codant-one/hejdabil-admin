<?php

namespace App\Http\Controllers;

use App\Http\Requests\NotificationRequest;

use App\Events\NotificationsWebsocketEvent;
use App\Events\UserNotificationEvent;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    /**
     * Enviar una notificación via WebSocket
     * Puede enviar a un usuario específico (canal privado) o a todos (canal público)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function send(NotificationRequest $request)
    {
        try {

            // Preparar el mensaje de notificación
            $message = (object) [
                'title' => $request->input('title'),
                'subtitle' => $request->input('subtitle', ''),
                'time' => now()->format('H:i:s'),
                'img' => $request->input('img'),
                'color' => $request->input('color', 'primary'),
                'icon' => $request->input('icon', 'tabler-bell'),
                'text' => $request->input('text'),
            ];

            $userId = $request->input('user_id');
            
            if ($userId) {//privada
                $evento = new UserNotificationEvent($message, $userId);
                Event::dispatch($evento);
            } else {//publica
                $evento = new NotificationsWebsocketEvent($message);
                Event::dispatch($evento);
            }

            return response()->json([
                'success' => true,
                'message' => 'Meddelande skickat korrekt',
                'data' => [
                    'title' => $message->title,
                    'text' => $message->text,
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
