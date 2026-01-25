<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Motivo extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'motivos';
    public $timestamps = false;

    protected $fillable = [
        'motivo',
        'descripcion',
        'monto_base',
        'recurrente',
    ];

    protected $casts = [
        'monto_base' => 'decimal:2',
        'recurrente' => 'boolean',
    ];

    /**
     * Obtener pagos de este motivo
     */
    public function pagos()
    {
        return $this->hasMany(Pago::class, 'id_motivo', '_id');
    }

    /**
     * Obtener motivos recurrentes
     */
    public function scopeRecurrentes($query)
    {
        return $query->where('recurrente', true);
    }
}
