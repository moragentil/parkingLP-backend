<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estacionamiento extends Model
{
    use HasFactory;

    protected $table = 'estacionamientos';
    
    protected $fillable = [
        'vehiculo_id',
        'zona_id',
        'fecha_inicio',
        'hora_inicio',
        'fecha_fin',
        'hora_fin',
        'estado',
        'alarma_programada',
        'monto_pagado',
        'sem_transaction_id',
        'latitud',
        'longitud',
        'direccion'
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'hora_inicio' => 'datetime',
        'fecha_fin' => 'date',
        'hora_fin' => 'datetime',
        'alarma_programada' => 'boolean',
        'monto_pagado' => 'decimal:2',
        'latitud' => 'decimal:8',
        'longitud' => 'decimal:8'
    ];

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function zona()
    {
        return $this->belongsTo(Zona::class);
    }

    public function alarma()
    {
        return $this->hasOne(Alarma::class);
    }

}