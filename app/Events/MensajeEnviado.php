<?php

namespace App\Events;

use App\Models\Mensaje;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MensajeEnviado implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $mensaje;

    /**
     * Create a new event instance.
     */
    public function __construct(Mensaje $mensaje)
    {
        $this->mensaje = $mensaje;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel
    {
        // Broadcast to private channel between remitente and destinatario
        if ($this->mensaje->tipo === 'personal') {
            return new PrivateChannel('chat.' . $this->mensaje->remitente . '.' . $this->mensaje->destinatario);
        } else {
            return new PrivateChannel('chat.departamento.' . $this->mensaje->id_depaR);
        }
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => (string)$this->mensaje->_id,
            'remitente_id' => $this->mensaje->remitente,
            'destinatario_id' => $this->mensaje->destinatario,
            'contenido' => $this->mensaje->mensaje,
            'tipo' => $this->mensaje->tipo,
            'id_depa' => $this->mensaje->id_depaR,
            'leido' => $this->mensaje->leido,
            'fecha' => $this->mensaje->fecha,
        ];
    }

    /**
     * Get the name of the event to be broadcast.
     */
    public function broadcastAs(): string
    {
        return 'mensaje-enviado';
    }
}
