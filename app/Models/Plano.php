<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plano extends Model
{
    use HasFactory;

    protected $fillable = [
        'version',
        'fecha_actualizacion',
        'zonas',
        'descripcion',
        'activo'
    ];

    protected $casts = [
        'fecha_actualizacion' => 'date',
        'zonas' => 'array',
        'activo' => 'boolean'
    ];

    public function getZonasRelacionadas()
    {
        return Zona::whereIn('id', $this->zonas)->get();
    }
}