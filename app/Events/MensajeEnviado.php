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
        
        \Log::info('Evento MensajeEnviado creado', [
            'remitente' => $mensaje->remitente,
            'destinatario' => $mensaje->destinatario,
            'tipo' => $mensaje->tipo,
            'id_depaR' => $mensaje->id_depaR,
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel
    {
        // Broadcast to private channel between remitente and destinatario
        if ($this->mensaje->tipo === 'personal') {
            $channel = 'chat.' . $this->mensaje->remitente . '.' . $this->mensaje->destinatario;
            \Log::info('Broadcasting mensaje a canal personal', ['channel' => $channel]);
            return new PrivateChannel($channel);
        } else {
            $channel = 'chat.departamento.' . $this->mensaje->id_depaR;
            \Log::info('Broadcasting mensaje a canal departamento', ['channel' => $channel]);
            return new PrivateChannel($channel);
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
