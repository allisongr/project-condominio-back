<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Mantenimiento extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'mantenimiento';
    public $timestamps = false;

    protected $fillable = [
        'mes',
        'anio',
        'id_depa',
        'completado',
        'monto',
        'id_pago',
        'fecha_vencimiento',
        'notas',
    ];

    protected $casts = [
        'mes' => 'integer',
        'anio' => 'integer',
        'id_depa' => 'integer',
        'completado' => 'boolean',
        'monto' => 'decimal:2',
        'fecha_vencimiento' => 'datetime',
    ];

    /**
     * Obtener mantenimiento de un departamento
     */
    public function scopeDelDepartamento($query, $depa_id)
    {
        return $query->where('id_depa', $depa_id);
    }

    /**
     * Obtener mantenimiento por período
     */
    public function scopeDelPeriodo($query, $mes, $anio)
    {
        return $query->where('mes', $mes)->where('anio', $anio);
    }

    /**
     * Obtener mantenimiento completado
     */
    public function scopeCompletado($query)
    {
        return $query->where('completado', true);
    }

    /**
     * Obtener pago asociado
     */
    public function pago()
    {
        return $this->belongsTo(Pago::class, 'id_pago', '_id');
    }
}
