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
            
            // Verificar que el vehículo pertenezca al usuario
            $vehiculo = $usuario->vehiculos()->findOrFail($datos['vehiculo_id']);
            
            // Verificar que no tenga un estacionamiento activo
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

            // Verificar zona
            $zona = $this->verificarZona($datos['latitud'], $datos['longitud']);
            
            // Verificar si es zona prohibida
            if ($zona && $zona->es_prohibido_estacionar) {
                return [
                    'status' => false,
                    'message' => 'No se puede estacionar en esta zona (zona prohibida)',
                    'zona' => $zona,
                    'status_code' => 422
                ];
            }

            // Crear el estacionamiento
            $estacionamiento = Estacionamiento::create([
                'vehiculo_id' => $vehiculo->id,
                'zona_id' => $zona ? $zona->id : null,
                'fecha_inicio' => now()->toDateString(),
                'hora_inicio' => now()->toTimeString(),
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

    public function finalizarEstacionamiento($usuarioId, $estacionamientoId)
    {
        DB::beginTransaction();
        try {
            $usuario = Usuario::findOrFail($usuarioId);
            
            $estacionamiento = Estacionamiento::whereHas('vehiculo', function ($query) use ($usuarioId) {
                $query->where('usuario_id', $usuarioId);
            })
            ->where('estado', 'activo')
            ->findOrFail($estacionamientoId);
            
            // Finalizar estacionamiento
            $estacionamiento->update([
                'fecha_fin' => now()->toDateString(),
                'hora_fin' => now()->toTimeString(),
                'estado' => 'finalizado'
            ]);

            // Calcular costo si está en zona paga
            $costo = 0;
            if ($estacionamiento->zona && !$estacionamiento->zona->es_prohibido_estacionar) {
                $costo = $this->calcularCosto($estacionamiento->id);
                $estacionamiento->update(['monto_pagado' => $costo]);
            }

            // Desactivar alarma si existe
            if ($estacionamiento->alarma) {
                $estacionamiento->alarma->update(['activa' => false]);
            }

            DB::commit();
            
            $estacionamiento->load(['vehiculo', 'zona', 'alarma']);
            
            return [
                'status' => true,
                'message' => 'Estacionamiento finalizado exitosamente',
                'estacionamiento' => $estacionamiento,
                'costo_total' => $costo,
                'status_code' => 200
            ];
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return [
                'status' => false,
                'message' => 'Estacionamiento activo no encontrado',
                'status_code' => 404
            ];
        } catch (\Exception $e) {
            DB::rollBack();
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
                    'status_code' => 404
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
            
            if (!$estacionamiento->zona || $estacionamiento->zona->es_prohibido_estacionar) {
                return 0;
            }

            $inicio = Carbon::parse($estacionamiento->fecha_inicio . ' ' . $estacionamiento->hora_inicio);
            $fin = $estacionamiento->fecha_fin && $estacionamiento->hora_fin 
                ? Carbon::parse($estacionamiento->fecha_fin . ' ' . $estacionamiento->hora_fin)
                : now();

            $horas = $inicio->diffInHours($fin, false);
            $horasRedondeadas = ceil($horas); // Redondear hacia arriba

            return $horasRedondeadas * $estacionamiento->zona->tarifa_por_hora;
        } catch (\Exception $e) {
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