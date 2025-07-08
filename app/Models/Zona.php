<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Zona extends Model
{
    use HasFactory;

    protected $table = 'zonas';
    
    protected $fillable = [
        'nombre',
        'poligono_coordenadas', // JSON con coordenadas del polígono
        'hora_inicio',
        'hora_fin',
        'dias_habilitados', // (ej. ["lunes", "martes", "miércoles"])
        'tarifa_por_hora',
        'color_mapa',
        'es_prohibido_estacionar', // Indica si es una zona de prohibición
        'activa',
        'descripcion'
    ];

    protected $casts = [
        'poligono_coordenadas' => 'array',
        'dias_habilitados' => 'array',
        'hora_inicio' => 'datetime',
        'hora_fin' => 'datetime',
        'es_prohibido_estacionar' => 'boolean',
        'activa' => 'boolean',
        'tarifa_por_hora' => 'decimal:2'
    ];
    
    public function estacionamientos()
    {
        return $this->hasMany(Estacionamiento::class);
    }
}