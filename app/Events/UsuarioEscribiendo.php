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
    public $destinatario_id;
    public $id_depa;

    /**
     * Create a new event instance.
     */
    public function __construct($usuario_id, $destinatario_id, $id_depa)
    {
        $this->usuario_id = $usuario_id;
        $this->destinatario_id = $destinatario_id;
        $this->id_depa = $id_depa;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('typing.' . $this->usuario_id . '.' . $this->destinatario_id);
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'usuario_id' => $this->usuario_id,
            'destinatario_id' => $this->destinatario_id,
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
