<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Zona extends Model
{
    use HasFactory;

    protected $table = 'zonas';
    
    protected $fillable = [
        'nombre',
        'poligono_coordenadas',
        'color_mapa',
        'es_prohibido_estacionar',
        'activa',
        'descripcion'
    ];

    protected $casts = [
        'poligono_coordenadas' => 'array',
        'es_prohibido_estacionar' => 'boolean',
        'activa' => 'boolean'
    ];
    
    public function estacionamientos()
    {
        return $this->hasMany(Estacionamiento::class);
    }

    public function horarios()
    {
        return $this->hasMany(ZonaHorario::class);
    }

    /**
     * Verificar si la zona está activa en un día y hora específicos
     */
    public function estaActivaEnDiaYHora($diaSemana, $hora)
    {
        if (!$this->activa) {
            return false;
        }

        $horario = $this->horarios()
            ->where('dia_semana', $diaSemana)
            ->where('activo', true)
            ->first();

        if (!$horario) {
            return false;
        }

        return $horario->estaEnHorario($hora);
    }

    /**
     * Obtener la tarifa aplicable en una hora específica
     */
    public function obtenerTarifaEnHora($hora)
    {
        if ($this->es_prohibido_estacionar) {
            return 0;
        }

        $tarifa = TarifaHorario::obtenerTarifaPorHora($hora);
        return $tarifa ? $tarifa->precio_por_hora : 0;
    }

    /**
     * Obtener todos los horarios de la zona agrupados por día
     */
    public function getHorariosPorDia()
    {
        return $this->horarios()
            ->where('activo', true)
            ->orderBy('dia_semana')
            ->get()
            ->groupBy('dia_semana');
    }
}