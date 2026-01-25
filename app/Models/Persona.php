<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Persona extends Model
{
    protected $fillable = [
        'nombre',
        'apellido_p',
        'apellido_m',
        'celular',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Obtener el usuario asociado a la persona
     */
    public function usuario(): HasOne
    {
        return $this->hasOne(Usuario::class, 'id_persona');
    }

    /**
     * Obtener las relaciones persona-departamento-rol
     */
    public function perDeps(): HasMany
    {
        return $this->hasMany(PerDep::class, 'id_persona');
    }

    /**
     * Obtener el nombre completo de la persona
     */
    public function getNombreCompletoAttribute(): string
    {
        $apellidos = $this->apellido_p;
        if ($this->apellido_m) {
            $apellidos .= ' ' . $this->apellido_m;
        }
        return "$this->nombre $apellidos";
    }
}
