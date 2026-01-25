<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoPago extends Model
{
    public $timestamps = false;
    protected $table = 'tipos_pago';

    protected $fillable = [
        'tipo',
    ];
}
