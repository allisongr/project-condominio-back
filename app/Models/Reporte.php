<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Reporte extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'reportes';

    protected $fillable = [
        'id_usuario',
        'reporte',
        'tipo',
        'prioridad',
        'estado',
        'fecha',
        'imagenes',
        'comentarios',
        'resolucion',
    ];

    protected $casts = [
        'id_usuario' => 'integer',
        'fecha' => 'datetime',
        'imagenes' => 'array',
        'comentarios' => 'array',
        'resolucion' => 'array',
    ];

    /**
     * Obtener reportes por estado
     */
    public function scopePorEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Obtener reportes por prioridad
     */
    public function scopePorPrioridad($query, $prioridad)
    {
        return $query->where('prioridad', $prioridad);
    }

    /**
     * Obtener reportes de un usuario
     */
    public function scopeDelUsuario($query, $usuario_id)
    {
        return $query->where('id_usuario', $usuario_id);
    }

    /**
     * Agregar comentario
     */
    public function agregarComentario($usuario_id, $comentario)
    {
        $this->comentarios = $this->comentarios ?? [];
        $this->comentarios[] = [
            'usuario_id' => $usuario_id,
            'comentario' => $comentario,
            'fecha' => now(),
        ];
        return $this->save();
    }
}
