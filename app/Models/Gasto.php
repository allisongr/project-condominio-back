<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Gasto extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'gastos';
    public $timestamps = false;

    protected $fillable = [
        'monto',
        'descripcion',
        'categoria',
        'fecha',
        'comprobante',
        'aprobado',
        'aprobado_por',
        'notas',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha' => 'datetime',
        'aprobado' => 'boolean',
        'aprobado_por' => 'integer',
    ];

    /**
     * Obtener gastos aprobados
     */
    public function scopeAprobados($query)
    {
        return $query->where('aprobado', true);
    }

    /**
     * Obtener gastos por categoría
     */
    public function scopePorCategoria($query, $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    /**
     * Obtener gastos en un rango de fechas
     */
    public function scopeEntreFechas($query, $inicio, $fin)
    {
        return $query->whereBetween('fecha', [$inicio, $fin]);
    }
}
