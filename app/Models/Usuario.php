<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;

class Usuario extends Model implements Authenticatable
{
    use AuthenticatableTrait;

    protected $fillable = [
        'id_persona',
        'email',
        'pass',
        'admin',
    ];

    protected $casts = [
        'admin' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'pass',
    ];
    
    /**
     * Get the password for authentication.
     */
    public function getAuthPassword()
    {
        return $this->pass;
    }

    /**
     * Obtener la persona asociada al usuario
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'id_persona');
    }
}
