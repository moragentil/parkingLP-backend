<?php

namespace App\Services\Implementation;

use App\Models\Alarma;
use App\Models\Estacionamiento;
use App\Models\Usuario;
use App\Services\Interface\AlarmaServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AlarmaService implements AlarmaServiceInterface
{
    public function obtenerAlarmasUsuario($usuarioId)
    {
        try {
            $usuario = Usuario::findOrFail($usuarioId);
            
            $alarmas = Alarma::whereHas('estacionamiento.vehiculo', function ($query) use ($usuarioId) {
                $query->where('usuario_id', $usuarioId);
            })
            ->with(['estacionamiento.vehiculo', 'estacionamiento.zona'])
            ->orderBy('hora_alarma', 'desc')
            ->get();
            
            return [
                'status' => true,
                'message' => 'Alarmas obtenidas exitosamente',
                'alarmas' => $alarmas,
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
                'message' => 'Error al obtener alarmas',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function crearAlarma($estacionamientoId, array $datos)
    {
        DB::beginTransaction();
        try {
            // Verificar que el estacionamiento existe
            $estacionamiento = Estacionamiento::findOrFail($estacionamientoId);
            
            // Verificar que el estacionamiento esté activo
            if ($estacionamiento->estado !== 'activo') {
                return [
                    'status' => false,
                    'message' => 'Solo se pueden crear alarmas para estacionamientos activos',
                    'status_code' => 422
                ];
            }

            // Verificar que no tenga ya una alarma activa
            $alarmaExistente = Alarma::where('estacionamiento_id', $estacionamientoId)
                ->where('activa', true)
                ->first();
                
            if ($alarmaExistente) {
                return [
                    'status' => false,
                    'message' => 'El estacionamiento ya tiene una alarma activa',
                    'status_code' => 409
                ];
            }

            // Parsear hora de la alarma
            $horaAlarma = isset($datos['hora_alarma']) 
                ? Carbon::parse($datos['hora_alarma'])
                : now()->addMinutes(30); // Default: 30 minutos

            // Verificar que la hora de alarma sea futura
            if ($horaAlarma->isPast()) {
                return [
                    'status' => false,
                    'message' => 'La hora de alarma debe ser futura',
                    'status_code' => 422
                ];
            }

            // Crear la alarma
            $alarma = Alarma::create([
                'estacionamiento_id' => $estacionamientoId,
                'hora_alarma' => $horaAlarma,
                'mensaje' => $datos['mensaje'] ?? $this->generarMensajeDefault($estacionamiento),
                'activa' => true,
                'enviada' => false
            ]);

            // Marcar el estacionamiento como que tiene alarma programada
            $estacionamiento->update(['alarma_programada' => true]);

            DB::commit();
            
            $alarma->load(['estacionamiento.vehiculo', 'estacionamiento.zona']);
            
            return [
                'status' => true,
                'message' => 'Alarma creada exitosamente',
                'alarma' => $alarma,
                'status_code' => 201
            ];
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return [
                'status' => false,
                'message' => 'Estacionamiento no encontrado',
                'status_code' => 404
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'status' => false,
                'message' => 'Error al crear alarma',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function obtenerAlarma($alarmaId)
    {
        try {
            $alarma = Alarma::with(['estacionamiento.vehiculo', 'estacionamiento.zona'])
                ->findOrFail($alarmaId);
            
            return [
                'status' => true,
                'message' => 'Alarma obtenida exitosamente',
                'alarma' => $alarma,
                'status_code' => 200
            ];
        } catch (ModelNotFoundException $e) {
            return [
                'status' => false,
                'message' => 'Alarma no encontrada',
                'status_code' => 404
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Error al obtener alarma',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function actualizarAlarma($alarmaId, array $datos)
    {
        DB::beginTransaction();
        try {
            $alarma = Alarma::findOrFail($alarmaId);
            
            // Verificar que la alarma esté activa
            if (!$alarma->activa) {
                return [
                    'status' => false,
                    'message' => 'No se puede actualizar una alarma inactiva',
                    'status_code' => 422
                ];
            }

            // Preparar datos de actualización
            $datosActualizacion = [];
            
            if (isset($datos['hora_alarma'])) {
                $nuevaHora = Carbon::parse($datos['hora_alarma']);
                
                // Verificar que la nueva hora sea futura
                if ($nuevaHora->isPast()) {
                    return [
                        'status' => false,
                        'message' => 'La nueva hora de alarma debe ser futura',
                        'status_code' => 422
                    ];
                }
                
                $datosActualizacion['hora_alarma'] = $nuevaHora;
            }
            
            if (isset($datos['mensaje'])) {
                $datosActualizacion['mensaje'] = $datos['mensaje'];
            }
            
            if (isset($datos['activa'])) {
                $datosActualizacion['activa'] = $datos['activa'];
            }

            $alarma->update($datosActualizacion);

            DB::commit();
            
            return [
                'status' => true,
                'message' => 'Alarma actualizada exitosamente',
                'alarma' => $alarma->fresh(['estacionamiento.vehiculo', 'estacionamiento.zona']),
                'status_code' => 200
            ];
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return [
                'status' => false,
                'message' => 'Alarma no encontrada',
                'status_code' => 404
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'status' => false,
                'message' => 'Error al actualizar alarma',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function eliminarAlarma($alarmaId)
    {
        DB::beginTransaction();
        try {
            $alarma = Alarma::findOrFail($alarmaId);
            
            // Actualizar el estacionamiento
            $alarma->estacionamiento->update(['alarma_programada' => false]);
            
            // Eliminar la alarma
            $alarma->delete();

            DB::commit();
            
            return [
                'status' => true,
                'message' => 'Alarma eliminada exitosamente',
                'status_code' => 200
            ];
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return [
                'status' => false,
                'message' => 'Alarma no encontrada',
                'status_code' => 404
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'status' => false,
                'message' => 'Error al eliminar alarma',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function marcarComoEnviada($alarmaId)
    {
        try {
            $alarma = Alarma::findOrFail($alarmaId);
            
            $alarma->update([
                'enviada' => true,
                'fecha_envio' => now()
            ]);
            
            return [
                'status' => true,
                'message' => 'Alarma marcada como enviada',
                'alarma' => $alarma,
                'status_code' => 200
            ];
        } catch (ModelNotFoundException $e) {
            return [
                'status' => false,
                'message' => 'Alarma no encontrada',
                'status_code' => 404
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Error al marcar alarma como enviada',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function obtenerAlarmasActivas()
    {
        try {
            $alarmas = Alarma::where('activa', true)
                ->where('enviada', false)
                ->with(['estacionamiento.vehiculo', 'estacionamiento.zona'])
                ->orderBy('hora_alarma', 'asc')
                ->get();
            
            return [
                'status' => true,
                'message' => 'Alarmas activas obtenidas exitosamente',
                'alarmas' => $alarmas,
                'status_code' => 200
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Error al obtener alarmas activas',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function obtenerAlarmasPendientes()
    {
        try {
            $alarmas = Alarma::where('activa', true)
                ->where('enviada', false)
                ->where('hora_alarma', '<=', now())
                ->with(['estacionamiento.vehiculo', 'estacionamiento.zona'])
                ->orderBy('hora_alarma', 'asc')
                ->get();
            
            return [
                'status' => true,
                'message' => 'Alarmas pendientes obtenidas exitosamente',
                'alarmas' => $alarmas,
                'cantidad' => $alarmas->count(),
                'status_code' => 200
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Error al obtener alarmas pendientes',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function desactivarAlarma($alarmaId)
    {
        DB::beginTransaction();
        try {
            $alarma = Alarma::findOrFail($alarmaId);
            
            $alarma->update(['activa' => false]);
            
            // Actualizar el estacionamiento
            $alarma->estacionamiento->update(['alarma_programada' => false]);

            DB::commit();
            
            return [
                'status' => true,
                'message' => 'Alarma desactivada exitosamente',
                'alarma' => $alarma,
                'status_code' => 200
            ];
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return [
                'status' => false,
                'message' => 'Alarma no encontrada',
                'status_code' => 404
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'status' => false,
                'message' => 'Error al desactivar alarma',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    /**
     * Generar mensaje por defecto para la alarma
     */
    private function generarMensajeDefault($estacionamiento)
    {
        $zonaNombre = $estacionamiento->zona ? $estacionamiento->zona->nombre : 'la zona';
        $vehiculoPatente = $estacionamiento->vehiculo->patente ?? 'tu vehículo';
        
        if ($estacionamiento->zona && $estacionamiento->zona->hora_fin) {
            $horaFin = Carbon::parse($estacionamiento->zona->hora_fin)->format('H:i');
            return "Recordatorio: Tu vehículo {$vehiculoPatente} estacionado en {$zonaNombre} debe ser retirado antes de las {$horaFin}h.";
        }
        
        return "Recordatorio: Tu vehículo {$vehiculoPatente} estacionado en {$zonaNombre} debe ser retirado pronto.";
    }
}