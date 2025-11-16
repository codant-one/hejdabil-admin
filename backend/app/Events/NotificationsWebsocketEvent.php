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

class NotificationsWebsocketEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct($message)
    {
        // Normaliza el payload de entrada a un arreglo asociativo
        $data = is_array($message)
            ? $message
            : (is_object($message) ? (array) $message : []);

        // Mapea y asegura valores por defecto vacíos si faltan
        $this->message = [
            'title'   => isset($data['title']) ? (string) $data['title'] : null,
            'subtitle' => isset($data['subtitle']) ? (string) $data['subtitle'] : null,
            'time'    => isset($data['time']) ? (string) $data['time'] : null,
            'img'     => isset($data['img']) ? (string) $data['img'] : null,
            'color'   => isset($data['color']) ? (string) $data['color'] : null,
            'icon'    => isset($data['icon']) ? (string) $data['icon'] : null,
            'text'    => isset($data['text']) ? (string) $data['text'] : null,
        ];
        //\Log::info('Evento NotificationsWebsocketEvent payload normalizado', $this->mensaje);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('notifications-channel')
        ];
    }

    /**
     * El nombre del evento tal como será escuchado por Echo.
     * Opcional: Por defecto es el nombre de la clase
     */
    public function broadcastAs()
    {
        return 'notifications-channel';
    }
}
