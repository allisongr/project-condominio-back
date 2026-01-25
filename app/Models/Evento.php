<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Evento extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'eventos';

    protected $fillable = [
        'fecha',
        'descripcion',
        'tipo',
        'ubicacion',
        'organizador',
        'asistentes_confirmados',
        'activo',
        'created_at',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'organizador' => 'integer',
        'asistentes_confirmados' => 'array',
        'activo' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Obtener eventos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Obtener eventos por tipo
     */
    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Obtener asistencias de este evento
     */
    public function asistencias()
    {
        return $this->hasMany(Asistencia::class, 'id_evento', '_id');
    }

    /**
     * Obtener respuestas de preguntas de este evento
     */
    public function respuestas()
    {
        return $this->hasMany(Respuesta::class, 'id_evento', '_id');
    }
}
