<?php

namespace App\Services;

use App\Models\Estacionamiento;
use App\Models\Zona;
use App\Models\Alarma;
use Carbon\Carbon;

class EstacionamientoService
{
    public function verificarZona($latitud, $longitud)
    {
        $zonas = Zona::all();
        
        foreach ($zonas as $zona) {
            if ($this->puntoEnPoligono($latitud, $longitud, $zona->poligono_coordenadas)) {
                return $zona;
            }
        }
        
        return null;
    }

    public function crearEstacionamiento($vehiculoId, $latitud, $longitud, $direccion = null)
    {
        $zona = $this->verificarZona($latitud, $longitud);
        
        $estacionamiento = Estacionamiento::create([
            'vehiculo_id' => $vehiculoId,
            'zona_id' => $zona ? $zona->id : null,
            'fecha_inicio' => now()->toDateString(),
            'hora_inicio' => now()->toTimeString(),
            'latitud' => $latitud,
            'longitud' => $longitud,
            'direccion' => $direccion,
            'estado' => 'activo'
        ]);

        // Programar alarma si está en zona paga
        if ($zona) {
            $this->programarAlarma($estacionamiento, $zona);
        }

        return $estacionamiento;
    }

    private function puntoEnPoligono($lat, $lng, $poligono)
    {
        // Implementar algoritmo de ray casting
        // Por ahora retorna true como ejemplo
        return true;
    }

    private function programarAlarma($estacionamiento, $zona)
    {
        $horaFin = Carbon::createFromFormat('H:i:s', $zona->hora_fin);
        $alarmaHora = $horaFin->subMinutes(30); // 30 min antes

        Alarma::create([
            'estacionamiento_id' => $estacionamiento->id,
            'fecha_alarma' => now()->toDateString(),
            'hora_alarma' => $alarmaHora->toTimeString(),
            'mensaje' => 'Tu estacionamiento vence en 30 minutos',
            'tipo' => 'vencimiento'
        ]);
    }
}