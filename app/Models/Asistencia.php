<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Asistencia extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'asistencia';

    protected $fillable = [
        'id_persona',
        'id_evento',
        'asistio',
        'fecha_registro',
    ];

    protected $casts = [
        'id_persona' => 'integer',
        'asistio' => 'boolean',
        'fecha_registro' => 'datetime',
    ];

    /**
     * Obtener el evento
     */
    public function evento()
    {
        return $this->belongsTo(Evento::class, 'id_evento', '_id');
    }

    /**
     * Obtener asistentes de un evento
     */
    public function scopeDelEvento($query, $evento_id)
    {
        return $query->where('id_evento', $evento_id);
    }

    /**
     * Obtener eventos de una persona
     */
    public function scopeDelPersona($query, $persona_id)
    {
        return $query->where('id_persona', $persona_id);
    }
}
