<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Pago extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'pagos';

    protected $fillable = [
        'id_depa',
        'monto',
        'id_tipo',
        'fecha',
        'id_motivo',
        'descripcion',
        'comprobante',
        'efectuado',
        'id_reporte',
        'periodo',
    ];

    protected $casts = [
        'id_depa' => 'integer',
        'monto' => 'decimal:2',
        'id_tipo' => 'integer',
        'fecha' => 'datetime',
        'efectuado' => 'boolean',
        'periodo' => 'array',
    ];

    /**
     * Obtener pagos de un departamento
     */
    public function scopeDelDepartamento($query, $depa_id)
    {
        return $query->where('id_depa', $depa_id);
    }

    /**
     * Obtener pagos efectuados
     */
    public function scopeEfectuados($query)
    {
        return $query->where('efectuado', true);
    }

    /**
     * Obtener pagos pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('efectuado', false);
    }

    /**
     * Obtener motivo del pago
     */
    public function motivo()
    {
        return $this->belongsTo(Motivo::class, 'id_motivo', '_id');
    }

    /**
     * Obtener reporte asociado
     */
    public function reporte()
    {
        return $this->belongsTo(Reporte::class, 'id_reporte', '_id');
    }
}
