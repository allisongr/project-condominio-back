<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Carro extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'id_depa',
        'placa',
        'marca',
        'modelo',
        'color',
    ];

    /**
     * Obtener el departamento del carro
     */
    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class, 'id_depa');
    }
}
