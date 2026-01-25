<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Departamento extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'depa',
        'moroso',
        'codigo',
    ];

    protected $casts = [
        'moroso' => 'boolean',
    ];

    /**
     * Obtener los carros del departamento
     */
    public function carros(): HasMany
    {
        return $this->hasMany(Carro::class, 'id_depa');
    }

    /**
     * Obtener los controles del departamento
     */
    public function controles(): HasMany
    {
        return $this->hasMany(Controle::class, 'id_depa');
    }

    /**
     * Obtener las relaciones persona-departamento-rol
     */
    public function perDeps(): HasMany
    {
        return $this->hasMany(PerDep::class, 'id_depa');
    }
}
