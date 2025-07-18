<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'hora_inicio' => 'datetime:H:i:s',
        'hora_fin' => 'datetime:H:i:s',
        'precio_por_hora' => 'decimal:2',
        'activa' => 'boolean'
    ];

    /**
     * Verificar si un horario específico está dentro del rango de esta tarifa
     */
    public function aplicaEnHorario($hora)
    {
        $horaCheck = is_string($hora) ? \Carbon\Carbon::createFromFormat('H:i:s', $hora) : $hora;
        $inicio = \Carbon\Carbon::createFromFormat('H:i:s', $this->hora_inicio);
        $fin = \Carbon\Carbon::createFromFormat('H:i:s', $this->hora_fin);

        return $horaCheck->between($inicio, $fin);
    }

    /**
     * Obtener la tarifa activa para una hora específica
     */
    public static function obtenerTarifaPorHora($hora)
    {
        return self::where('activa', true)
            ->get()
            ->first(function ($tarifa) use ($hora) {
                return $tarifa->aplicaEnHorario($hora);
            });
    }
}