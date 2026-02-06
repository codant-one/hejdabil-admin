<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UserNotificationEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $userId;

    /**
     * Create a new event instance.
     * 
     * @param array|object $message - Mensaje de la notificación
     * @param int $userId - ID del usuario que recibirá la notificación
     */
    public function __construct($message, $userId)
    {
        $this->userId = $userId;

        // Normaliza el payload de entrada a un arreglo asociativo
        $data = is_array($message)
            ? $message
            : (is_object($message) ? (array) $message : []);

        // Mapea y asegura valores por defecto vacíos si faltan
        $this->message = [
            'id'       => isset($data['id']) ? $data['id'] : null,
            'title'    => isset($data['title']) ? (string) $data['title'] : null,
            'subtitle' => isset($data['subtitle']) ? (string) $data['subtitle'] : null,
            'time'     => isset($data['time']) ? (string) $data['time'] : null,
            'img'      => isset($data['img']) ? (string) $data['img'] : null,
            'color'    => isset($data['color']) ? (string) $data['color'] : null,
            'icon'     => isset($data['icon']) ? (string) $data['icon'] : null,
            'text'     => isset($data['text']) ? (string) $data['text'] : null,
            'route'    => isset($data['route']) ? (string) $data['route'] : null,
            'read'     => isset($data['read']) ? $data['read'] : false,
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     * Canal privado específico para el usuario
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('notifications.' . $this->userId)
        ];
    }

    /**
     * El nombre del evento tal como será escuchado por Echo.
     */
    public function broadcastAs()
    {
        return 'user-notification';
    }

    /**
     * Datos adicionales que se enviarán con el evento
     */
    public function broadcastWith()
    {
        return [
            'message' => $this->message,
        ];
    }
}
