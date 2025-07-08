<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $table = 'usuarios';

    protected $fillable = [
        'nombre',
        'email',
        'telefono',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];


    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


    public function vehiculos()
    {
        return $this->hasMany(Vehiculo::class, 'usuario_id');
    }

    /**
     * Relación con estacionamientos a través de vehículos
     */
    public function estacionamientos()
    {
        return $this->hasMany(Estacionamiento::class, 'usuario_id');
    }
}