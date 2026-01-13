<?php

namespace App\Http\Controllers;

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
    public function send(Request $request)
    {
        // Validar datos de entrada
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'text' => 'required|string|max:1000',
            'color' => 'nullable|string|in:primary,success,info,warning,error',
            'icon' => 'nullable|string|max:100',
            'img' => 'nullable|url|max:500',
            'user_id' => 'nullable|integer|exists:users,id', // ID del usuario que recibirá la notificación
            'agreement_id' => 'nullable|string|max:100',
            'signed_by' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 422);
        }

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

        try {
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
