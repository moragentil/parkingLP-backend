<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZonaHorario extends Model
{
    use HasFactory;

    protected $table = 'zona_horarios';

    protected $fillable = [
        'zona_id',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
        'activo'
    ];

    protected $casts = [
        'hora_inicio' => 'datetime:H:i:s',
        'hora_fin' => 'datetime:H:i:s',
        'activo' => 'boolean'
    ];

    public function zona()
    {
        return $this->belongsTo(Zona::class);
    }

    /**
     * Verificar si un horario específico está dentro del rango permitido para este día
     */
    public function estaEnHorario($hora)
    {
        $horaCheck = is_string($hora) ? \Carbon\Carbon::createFromFormat('H:i:s', $hora) : $hora;
        $inicio = \Carbon\Carbon::createFromFormat('H:i:s', $this->hora_inicio);
        $fin = \Carbon\Carbon::createFromFormat('H:i:s', $this->hora_fin);

        return $horaCheck->between($inicio, $fin);
    }
}