<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PruebaWebsocketEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $mensaje;

    /**
     * Create a new event instance.
     */
    public function __construct($mensaje)
    {
        $this->mensaje = $mensaje;
        \Log::info('Evento PruebaWebsocketEvent creado con mensaje: ' . $mensaje);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('prueba-canal')
        ];
    }

    /**
     * El nombre del evento tal como será escuchado por Echo.
     * Opcional: Por defecto es el nombre de la clase
     */
    public function broadcastAs()
    {
        return 'mi.mensaje.prueba';
    }
}
