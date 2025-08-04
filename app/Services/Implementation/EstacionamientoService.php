<?php

namespace App\Services\Implementation;

use App\Models\Estacionamiento;
use App\Models\Zona;
use App\Models\Alarma;
use App\Models\Usuario;
use App\Models\Vehiculo;
use App\Services\Interface\EstacionamientoServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EstacionamientoService implements EstacionamientoServiceInterface
{
    public function obtenerEstacionamientosUsuario($usuarioId)
    {
        try {
            $usuario = Usuario::findOrFail($usuarioId);
            
            $estacionamientos = Estacionamiento::whereHas('vehiculo', function ($query) use ($usuarioId) {
                $query->where('usuario_id', $usuarioId);
            })
            ->with(['vehiculo', 'zona', 'alarma'])
            ->orderBy('created_at', 'desc')
            ->get();
            
            return [
                'status' => true,
                'message' => 'Estacionamientos obtenidos exitosamente',
                'estacionamientos' => $estacionamientos,
                'status_code' => 200
            ];
        } catch (ModelNotFoundException $e) {
            return [
                'status' => false,
                'message' => 'Usuario no encontrado',
                'status_code' => 404
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Error al obtener estacionamientos',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function iniciarEstacionamiento($usuarioId, array $datos)
    {
        DB::beginTransaction();
        try {
            $usuario = Usuario::findOrFail($usuarioId);
            $vehiculo = $usuario->vehiculos()->findOrFail($datos['vehiculo_id']);
            $estacionamientoActivo = Estacionamiento::where('vehiculo_id', $vehiculo->id)
                ->where('estado', 'activo')
                ->first();

            if ($estacionamientoActivo) {
                return [
                    'status' => false,
                    'message' => 'El vehículo ya tiene un estacionamiento activo',
                    'status_code' => 409
                ];
            }

            $zona = null;
            if (!empty($datos['zona_id'])) {
                $zona = Zona::find($datos['zona_id']);
            } else {
                $zona = $this->verificarZona($datos['latitud'], $datos['longituditud']);
            }

            if ($zona && $zona->es_prohibido_estacionar) {
                return [
                    'status' => false,
                    'message' => 'No se puede estacionar en esta zona (zona prohibida)',
                    'zona' => $zona,
                    'status_code' => 422
                ];
            }

            // CORRECCIÓN: Usar métodos de formato correctos
            $now = now();
            $estacionamiento = Estacionamiento::create([
                'vehiculo_id' => $vehiculo->id,
                'zona_id' => $zona ? $zona->id : null,
                'fecha_inicio' => $now->format('Y-m-d'), // Solo fecha
                'hora_inicio' => $now->format('H:i:s'),  // Solo hora
                'latitud' => $datos['latitud'],
                'longitud' => $datos['longitud'],
                'direccion' => $datos['direccion'] ?? null,
                'estado' => 'activo'
            ]);

            // Programar alarma si está en zona paga
            if ($zona && !$zona->es_prohibido_estacionar) {
                $this->programarAlarma($estacionamiento, $zona);
            }

            // Actualizar ubicación del vehículo
            $vehiculo->update([
                'ubicacion_actual_lat' => $datos['latitud'],
                'ubicacion_actual_lng' => $datos['longitud'],
                'fecha_estacionamiento' => now()->toDateString(),
                'hora_estacionamiento' => now()->toTimeString()
            ]);

            DB::commit();
            
            $estacionamiento->load(['vehiculo', 'zona', 'alarma']);
            
            return [
                'status' => true,
                'message' => 'Estacionamiento iniciado exitosamente',
                'estacionamiento' => $estacionamiento,
                'requiere_pago' => $zona ? !$zona->es_prohibido_estacionar : false,
                'zona_info' => $zona,
                'status_code' => 201
            ];
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return [
                'status' => false,
                'message' => 'Vehículo no encontrado',
                'status_code' => 404
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'status' => false,
                'message' => 'Error al iniciar estacionamiento',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function obtenerEstacionamiento($usuarioId, $estacionamientoId)
    {
        try {
            $usuario = Usuario::findOrFail($usuarioId);
            
            $estacionamiento = Estacionamiento::whereHas('vehiculo', function ($query) use ($usuarioId) {
                $query->where('usuario_id', $usuarioId);
            })
            ->with(['vehiculo', 'zona', 'alarma'])
            ->findOrFail($estacionamientoId);
            
            return [
                'status' => true,
                'message' => 'Estacionamiento obtenido exitosamente',
                'estacionamiento' => $estacionamiento,
                'status_code' => 200
            ];
        } catch (ModelNotFoundException $e) {
            return [
                'status' => false,
                'message' => 'Estacionamiento no encontrado',
                'status_code' => 404
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Error al obtener estacionamiento',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function finalizarEstacionamiento($usuarioId, $estacionamientoId, $horaFinForzada = null)
    {
        DB::beginTransaction();
        try {
            \Log::info("Finalizar estacionamiento - usuarioId: {$usuarioId}, estacionamientoId: {$estacionamientoId}, horaFinForzada: {$horaFinForzada}");

            $query = Estacionamiento::where('estado', 'activo');

            // Si es una acción del usuario, verificar que le pertenezca
            if ($usuarioId) {
                $usuario = Usuario::findOrFail($usuarioId);
                $query->whereHas('vehiculo', function ($q) use ($usuarioId) {
                    $q->where('usuario_id', $usuarioId);
                });
            }

            $estacionamiento = $query->findOrFail($estacionamientoId);

            \Log::info("Datos antes de finalizar: fecha_inicio={$estacionamiento->fecha_inicio}, hora_inicio={$estacionamiento->hora_inicio}, zona_id={$estacionamiento->zona_id}");

            // Finalizar estacionamiento
            $fechaFin = now()->format('Y-m-d');
            $horaFin = $horaFinForzada 
                ? Carbon::parse($horaFinForzada)->format('H:i:s')
                : now()->format('H:i:s');

            \Log::info("Actualizando fecha_fin={$fechaFin}, hora_fin={$horaFin}");

            $estacionamiento->update([
                'fecha_fin' => $fechaFin, // Solo fecha
                'hora_fin' => $horaFin,   // Solo hora
                'estado' => 'finalizado'
            ]);

            // Calcular costo si está en zona paga
            $costo = 0;
            if ($estacionamiento->zona && !$estacionamiento->zona->es_prohibido_estacionar) {
                \Log::info("Calculando costo para estacionamiento ID: {$estacionamiento->id}");
                $costo = $this->calcularCosto($estacionamiento->id);
                \Log::info("Costo calculado: {$costo}");
                $estacionamiento->update(['monto_pagado' => $costo]);
            } else {
                \Log::info("No se calcula costo (zona prohibida o sin zona)");
            }

            // Desactivar alarma si existe
            if ($estacionamiento->alarma) {
                $estacionamiento->alarma->update(['activa' => false]);
                \Log::info("Alarma desactivada para estacionamiento ID: {$estacionamiento->id}");
            }

            DB::commit();

            $estacionamiento->load(['vehiculo', 'zona', 'alarma']);

            \Log::info("Estacionamiento finalizado correctamente - ID: {$estacionamiento->id}");

            return [
                'status' => true,
                'message' => 'Estacionamiento finalizado exitosamente',
                'estacionamiento' => $estacionamiento,
                'costo_total' => $costo,
                'status_code' => 200
            ];
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            \Log::error("Estacionamiento activo no encontrado - ID: {$estacionamientoId}");
            return [
                'status' => false,
                'message' => 'Estacionamiento activo no encontrado',
                'status_code' => 404
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error al finalizar estacionamiento: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al finalizar estacionamiento',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function obtenerEstacionamientoActivo($usuarioId)
    {
        try {
            $usuario = Usuario::findOrFail($usuarioId);
            
            $estacionamiento = Estacionamiento::whereHas('vehiculo', function ($query) use ($usuarioId) {
                $query->where('usuario_id', $usuarioId);
            })
            ->where('estado', 'activo')
            ->with(['vehiculo', 'zona', 'alarma'])
            ->first();
            
            if (!$estacionamiento) {
                return [
                    'status' => false,
                    'message' => 'No hay estacionamiento activo',
                    'estacionamiento' => null,
                    'status_code' => 200 // Cambiado de 404 a 200
                ];
            }

            return [
                'status' => true,
                'message' => 'Estacionamiento activo obtenido exitosamente',
                'estacionamiento' => $estacionamiento,
                'status_code' => 200
            ];
        } catch (ModelNotFoundException $e) {
            return [
                'status' => false,
                'message' => 'Usuario no encontrado',
                'status_code' => 404
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Error al obtener estacionamiento activo',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function verificarZona($latitud, $longitud)
    {
        try {
            $zonas = Zona::where('activa', true)->get();
            
            foreach ($zonas as $zona) {
                if ($this->puntoEnPoligono($latitud, $longitud, $zona->poligono_coordenadas)) {
                    return $zona;
                }
            }
            
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function calcularCosto($estacionamientoId)
    {
        try {
            $estacionamiento = Estacionamiento::with('zona')->findOrFail($estacionamientoId);

            \Log::info("Calcular costo - Estacionamiento ID: {$estacionamientoId}");

            if (!$estacionamiento->zona || $estacionamiento->zona->es_prohibido_estacionar) {
                \Log::info("Zona no válida o prohibida, costo = 0");
                return 0;
            }

            // Extraer solo partes de fecha/hora necesarias
            $fechaInicio = $estacionamiento->fecha_inicio instanceof \DateTime
                ? $estacionamiento->fecha_inicio->format('Y-m-d')
                : substr($estacionamiento->fecha_inicio, 0, 10);

            $horaInicio = $estacionamiento->hora_inicio instanceof \DateTime
                ? $estacionamiento->hora_inicio->format('H:i:s')
                : (strlen($estacionamiento->hora_inicio) > 8 ? substr($estacionamiento->hora_inicio, 11, 8) : $estacionamiento->hora_inicio);

            $inicio = Carbon::createFromFormat('Y-m-d H:i:s', "{$fechaInicio} {$horaInicio}");

            // Manejar fecha/hora de fin
            $fin = now();
            if ($estacionamiento->fecha_fin && $estacionamiento->hora_fin) {
                $fechaFin = $estacionamiento->fecha_fin instanceof \DateTime
                    ? $estacionamiento->fecha_fin->format('Y-m-d')
                    : substr($estacionamiento->fecha_fin, 0, 10);

                $horaFin = $estacionamiento->hora_fin instanceof \DateTime
                    ? $estacionamiento->hora_fin->format('H:i:s')
                    : (strlen($estacionamiento->hora_fin) > 8 ? substr($estacionamiento->hora_fin, 11, 8) : $estacionamiento->hora_fin);

                $fin = Carbon::createFromFormat('Y-m-d H:i:s', "{$fechaFin} {$horaFin}");
            }

            \Log::info("DEBUG: Inicio: {$inicio}, Fin: {$fin}");

            $costoTotal = 0;
            $horaActual = $inicio->copy();

            while ($horaActual->lt($fin)) {
                $horaFin = $horaActual->copy()->addHour();
                if ($horaFin->gt($fin)) {
                    $horaFin = $fin;
                }

                $tarifa = $estacionamiento->zona->obtenerTarifaEnHora($horaActual->format('H:i:s'));
                $minutos = $horaActual->diffInMinutes($horaFin);
                $costoHora = ($tarifa * $minutos) / 60;

                \Log::info("Iteración: horaActual={$horaActual}, horaFin={$horaFin}, tarifa={$tarifa}, minutos={$minutos}, costoHora={$costoHora}");

                $costoTotal += $costoHora;
                $horaActual = $horaFin;
            }

            \Log::info("Costo total calculado: {$costoTotal}");

            return round($costoTotal, 2);
        } catch (\Exception $e) {
            \Log::error("Error al calcular costo: " . $e->getMessage());
            return 0;
        }
    }

    public function actualizarEstado($estacionamientoId, $estado)
    {
        try {
            $estacionamiento = Estacionamiento::findOrFail($estacionamientoId);
            
            $estacionamiento->update(['estado' => $estado]);
            
            return [
                'status' => true,
                'message' => 'Estado actualizado exitosamente',
                'estacionamiento' => $estacionamiento,
                'status_code' => 200
            ];
        } catch (ModelNotFoundException $e) {
            return [
                'status' => false,
                'message' => 'Estacionamiento no encontrado',
                'status_code' => 404
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Error al actualizar estado',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function finalizarEstacionamientosVencidos()
    {
        $ahora = now();
        $diaActual = strtolower($ahora->locale('es')->dayName);
        $horaActual = $ahora->format('H:i:00'); // Comparar hasta el minuto

        // Buscar todos los horarios de zona que finalizan en el minuto actual
        $horariosVencidos = \App\Models\ZonaHorario::where('dia_semana', $diaActual)
            ->where('hora_fin', $horaActual)
            ->where('activo', true)
            ->get();

        $contadorFinalizados = 0;

        foreach ($horariosVencidos as $horario) {
            $estacionamientosActivos = Estacionamiento::where('zona_id', $horario->zona_id)
                ->where('estado', 'activo')
                ->get();

            foreach ($estacionamientosActivos as $estacionamiento) {
                // Usar el método existente para finalizar, pasando null como usuarioId
                // ya que es una acción del sistema.
                $this->finalizarEstacionamiento(null, $estacionamiento->id, $horario->hora_fin);
                $contadorFinalizados++;
            }
        }

        return [
            'status' => true,
            'message' => "Se finalizaron {$contadorFinalizados} estacionamientos por vencimiento de zona.",
            'finalizados' => $contadorFinalizados
        ];
    }

    /**
     * Algoritmo básico para verificar si un punto está dentro de un polígono
     * Por ahora implementación simple - se puede mejorar con librerías especializadas
     */
    private function puntoEnPoligono($lat, $lng, $poligono)
    {
        // Implementación básica del algoritmo ray casting
        // Por simplicidad, retornamos true si hay coordenadas
        // En producción se debería implementar el algoritmo completo
        if (empty($poligono) || !is_array($poligono)) {
            return false;
        }
        
        // Verificación básica de proximidad (se puede mejorar)
        foreach ($poligono as $punto) {
            if (isset($punto['lat']) && isset($punto['lng'])) {
                $distancia = sqrt(
                    pow($lat - $punto['lat'], 2) + 
                    pow($lng - $punto['lng'], 2)
                );
                
                // Si está muy cerca de algún punto del polígono
                if ($distancia < 0.001) { // ~100 metros aproximadamente
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Programar alarma para el estacionamiento
     */
    private function programarAlarma($estacionamiento, $zona)
    {
        try {
            // Calcular hora de finalización según la zona
            $horaFin = Carbon::createFromFormat('H:i:s', $zona->hora_fin);
            $alarmaHora = $horaFin->subMinutes(30); // 30 min antes del vencimiento
            
            // Si la alarma sería antes de ahora, programarla para dentro de 30 minutos
            if ($alarmaHora->isPast()) {
                $alarmaHora = now()->addMinutes(30);
            }

            Alarma::create([
                'estacionamiento_id' => $estacionamiento->id,
                'hora_alarma' => $alarmaHora,
                'mensaje' => "Tu estacionamiento en {$zona->nombre} vence pronto. Límite: {$zona->hora_fin}",
                'activa' => true,
                'enviada' => false
            ]);

            // Marcar que tiene alarma programada
            $estacionamiento->update(['alarma_programada' => true]);
        } catch (\Exception $e) {
            // Log del error pero no fallar el estacionamiento
            \Log::error('Error al programar alarma: ' . $e->getMessage());
        }
    }
}