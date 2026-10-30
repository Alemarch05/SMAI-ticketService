<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    // Evita que Eloquent use una tabla real de PostgreSQL
    protected $table = null;

    protected $fillable = [
        'id',
        'name',
        'email',
        'role'
    ];

    /**
     * EVITA LA CONSULTA A LA BASE DE DATOS:
     * Cuando el middleware pida el usuario por su ID desde el token, 
     * en lugar de hacer 'SELECT * FROM users', retornamos una instancia en memoria.
     */
    public static function find($id)
    {
        $instance = new static();
        $instance->id = $id;
        return $instance;
    }

    // Métodos obligatorios de JWTSubject
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }
}