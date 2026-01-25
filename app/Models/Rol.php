<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rol extends Model
{
    protected $table = 'roles';
    public $timestamps = false;

    protected $fillable = [
        'rol',
    ];

    /**
     * Obtener las relaciones persona-departamento-rol
     */
    public function perDeps(): HasMany
    {
        return $this->hasMany(PerDep::class, 'id_rol');
    }
}
