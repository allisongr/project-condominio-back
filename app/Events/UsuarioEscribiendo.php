<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UsuarioEscribiendo implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $usuario_id;
    public $id_depa;
    public $nombre_usuario;

    /**
     * Create a new event instance.
     */
    public function __construct($usuario_id, $id_depa, $nombre_usuario)
    {
        $this->usuario_id = $usuario_id;
        $this->id_depa = $id_depa;
        $this->nombre_usuario = $nombre_usuario;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('chat.departamento.' . $this->id_depa);
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'usuario_id' => $this->usuario_id,
            'nombre_usuario' => $this->nombre_usuario,
            'id_depa' => $this->id_depa,
        ];
    }

    /**
     * Get the name of the event to be broadcast.
     */
    public function broadcastAs(): string
    {
        return 'usuario-escribiendo';
    }
}
