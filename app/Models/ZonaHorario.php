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

    // Getter personalizado para hora_inicio
    public function getHoraInicioAttribute($value)
    {
        try {
            if (!$value) return '00:00:00';
            
            // Si ya es un string en formato correcto, devolverlo
            if (is_string($value) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $value)) {
                return $value;
            }
            
            return Carbon::createFromFormat('H:i:s', $value)->format('H:i:s');
        } catch (\Exception $e) {
            return '00:00:00';
        }
    }

    // Getter personalizado para hora_fin
    public function getHoraFinAttribute($value)
    {
        try {
            if (!$value) return '23:59:59';
            
            // Si ya es un string en formato correcto, devolverlo
            if (is_string($value) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $value)) {
                return $value;
            }
            
            return Carbon::createFromFormat('H:i:s', $value)->format('H:i:s');
        } catch (\Exception $e) {
            return '23:59:59';
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
            $inicio = Carbon::createFromFormat('H:i:s', $this->hora_inicio);
            $fin = Carbon::createFromFormat('H:i:s', $this->hora_fin);

            return $horaCheck->between($inicio, $fin);
        } catch (\Exception $e) {
            return false;
        }
    }
}