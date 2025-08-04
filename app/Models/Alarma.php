<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alarma extends Model
{
    use HasFactory;

    protected $table = 'alarmas';
    
    protected $fillable = [
        'estacionamiento_id',
        'fecha_alarma',
        'hora_alarma',
        'mensaje',
        'activa',
        'enviada',
        'tipo'
    ];

    protected $casts = [
        'fecha_alarma' => 'date',
        'hora_alarma' => 'datetime',
        'activa' => 'boolean',
        'enviada' => 'boolean'
    ];

    public function estacionamiento()
    {
        return $this->belongsTo(Estacionamiento::class);
    }

}