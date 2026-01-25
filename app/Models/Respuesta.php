<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Respuesta extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'respuestas';
    public $timestamps = false;

    protected $fillable = [
        'id_pregunta',
        'id_asistente',
        'respuesta',
        'fecha_respuesta',
    ];

    protected $casts = [
        'id_pregunta' => 'integer',
        'id_asistente' => 'integer',
        'respuesta' => 'boolean',
        'fecha_respuesta' => 'datetime',
    ];

    /**
     * Obtener respuestas de una pregunta
     */
    public function scopeDePregunta($query, $pregunta_id)
    {
        return $query->where('id_pregunta', $pregunta_id);
    }

    /**
     * Obtener respuestas de una persona
     */
    public function scopeDelAsistente($query, $asistente_id)
    {
        return $query->where('id_asistente', $asistente_id);
    }
}
