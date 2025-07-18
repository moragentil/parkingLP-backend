<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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
        'activo' => 'boolean'
    ];

    // Manejar hora_inicio con validación
    public function getHoraInicioAttribute($value)
    {
        try {
            if (!$value) return null;
            return Carbon::createFromFormat('H:i:s', $value);
        } catch (\Exception $e) {
            return Carbon::createFromFormat('H:i:s', '00:00:00');
        }
    }

    // Manejar hora_fin con validación
    public function getHoraFinAttribute($value)
    {
        try {
            if (!$value) return null;
            return Carbon::createFromFormat('H:i:s', $value);
        } catch (\Exception $e) {
            return Carbon::createFromFormat('H:i:s', '23:59:59');
        }
    }

    public function zona()
    {
        return $this->belongsTo(Zona::class);
    }

    /**
     * Verificar si un horario específico está dentro del rango permitido para este día
     */
    public function estaEnHorario($hora)
    {
        try {
            $horaCheck = is_string($hora) ? Carbon::createFromFormat('H:i:s', $hora) : $hora;
            $inicio = $this->hora_inicio;
            $fin = $this->hora_fin;

            if (!$inicio || !$fin) return false;

            return $horaCheck->between($inicio, $fin);
        } catch (\Exception $e) {
            return false;
        }
    }
}