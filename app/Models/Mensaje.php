<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Mensaje extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'mensajes';

    protected $fillable = [
        'remitente',
        'destinatario',
        'id_depaR',
        'id_depaD',
        'mensaje',
        'fecha',
        'leido',
        'tipo',
    ];

    protected $casts = [
        'remitente' => 'integer',
        'destinatario' => 'integer',
        'id_depaR' => 'integer',
        'id_depaD' => 'integer',
        'fecha' => 'datetime',
        'leido' => 'boolean',
    ];

    /**
     * Obtener mensajes no leídos
     */
    public function scopeNoLeidos($query)
    {
        return $query->where('leido', false);
    }

    /**
     * Obtener mensajes de un remitente
     */
    public function scopeDelRemitente($query, $remitente_id)
    {
        return $query->where('remitente', $remitente_id);
    }

    /**
     * Obtener mensajes para un destinatario
     */
    public function scopeParaDestinatario($query, $destinatario_id)
    {
        return $query->where('destinatario', $destinatario_id);
    }
}
