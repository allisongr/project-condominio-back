<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerDep extends Model
{
    public $timestamps = false;
    protected $table = 'per_dep';

    protected $fillable = [
        'id_persona',
        'id_depa',
        'id_rol',
        'residente',
        'codigo',
        'fecha_inicio',
        'fecha_fin',
    ];

    protected $casts = [
        'residente' => 'boolean',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    /**
     * Obtener la persona
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'id_persona');
    }

    /**
     * Obtener el departamento
     */
    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class, 'id_depa');
    }

    /**
     * Obtener el rol
     */
    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'id_rol');
    }
}
