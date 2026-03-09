<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Model implements Authenticatable, MustVerifyEmail
{
    use AuthenticatableTrait, HasApiTokens, Notifiable, MustVerifyEmailTrait;

    protected $fillable = [
        'id_persona',
        'email',
        'pass',
        'admin',
        'email_verified_at',
        'email_verification_token',
        'password_reset_code',
        'password_reset_expires_at',
        'password_reset_token',
    ];

    protected $casts = [
        'admin' => 'boolean',
        'email_verified_at' => 'datetime',
        'password_reset_expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'pass',
        'email_verification_token',
        'password_reset_token',
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
