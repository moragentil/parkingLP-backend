<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehiculo extends Model
{
    use HasFactory;

    protected $fillable = [
        'patente',
        'usuario_id',
        'ubicacion_actual_lat',
        'ubicacion_actual_lng',
        'fecha_estacionamiento',
        'hora_estacionamiento',
        'activo'
    ];

    protected $casts = [
        'fecha_estacionamiento' => 'date',
        'hora_estacionamiento' => 'datetime:H:i:s',
        'activo' => 'boolean',
        'ubicacion_actual_lat' => 'decimal:8',
        'ubicacion_actual_lng' => 'decimal:8'
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function estacionamientos()
    {
        return $this->hasMany(Estacionamiento::class);
    }
}