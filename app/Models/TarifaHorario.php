<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TarifaHorario extends Model
{
    use HasFactory;

    protected $table = 'tarifas_horarios';

    protected $fillable = [
        'nombre',
        'hora_inicio',
        'hora_fin',
        'precio_por_hora',
        'descripcion',
        'activa'
    ];

    protected $casts = [
        'precio_por_hora' => 'decimal:2',
        'activa' => 'boolean'
    ];

    // Remover el cast automático para manejar errores manualmente
    public function getHoraInicioAttribute($value)
    {
        try {
            if (!$value) return null;
            return Carbon::createFromFormat('H:i:s', $value);
        } catch (\Exception $e) {
            return Carbon::createFromFormat('H:i:s', '00:00:00');
        }
    }

    public function getHoraFinAttribute($value)
    {
        try {
            if (!$value) return null;
            return Carbon::createFromFormat('H:i:s', $value);
        } catch (\Exception $e) {
            return Carbon::createFromFormat('H:i:s', '23:59:59');
        }
    }

    /**
     * Verificar si un horario específico está dentro del rango de esta tarifa
     */
    public function aplicaEnHorario($hora)
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

    /**
     * Obtener la tarifa activa para una hora específica
     */
    public static function obtenerTarifaPorHora($hora)
    {
        try {
            return self::where('activa', true)
                ->get()
                ->first(function ($tarifa) use ($hora) {
                    return $tarifa->aplicaEnHorario($hora);
                });
        } catch (\Exception $e) {
            return null;
        }
    }
}