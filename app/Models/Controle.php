<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Controle extends Model
{
    public $timestamps = false;
    protected $table = 'controles';

    protected $fillable = [
        'codigo',
        'id_depa',
        'activo',
        'fecha_asignacion',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'fecha_asignacion' => 'datetime',
    ];

    /**
     * Obtener el departamento del control
     */
    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class, 'id_depa');
    }
}
