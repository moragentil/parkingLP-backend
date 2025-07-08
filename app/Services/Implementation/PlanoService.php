<?php

namespace App\Services\Implementation;

use App\Models\Plano;
use App\Models\Zona;
use App\Services\Interface\PlanoServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PlanoService implements PlanoServiceInterface
{
    public function obtenerTodosLosPlanos()
    {
        try {
            $planos = Plano::orderBy('fecha_actualizacion', 'desc')->get();
            
            // Agregar zonas relacionadas a cada plano
            $planos->each(function ($plano) {
                $plano->zonas_relacionadas = $plano->getZonasRelacionadas();
            });
            
            return [
                'status' => true,
                'message' => 'Planos obtenidos exitosamente',
                'planos' => $planos,
                'status_code' => 200
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Error al obtener planos',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function obtenerPlanoVigente()
    {
        try {
            $plano = Plano::where('activo', true)
                ->orderBy('fecha_actualizacion', 'desc')
                ->first();
            
            if (!$plano) {
                return [
                    'status' => false,
                    'message' => 'No hay plano vigente',
                    'status_code' => 404
                ];
            }

            $plano->zonas_relacionadas = $plano->getZonasRelacionadas();
            
            return [
                'status' => true,
                'message' => 'Plano vigente obtenido exitosamente',
                'plano' => $plano,
                'status_code' => 200
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Error al obtener plano vigente',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function obtenerPlano($planoId)
    {
        try {
            $plano = Plano::findOrFail($planoId);
            $plano->zonas_relacionadas = $plano->getZonasRelacionadas();
            
            return [
                'status' => true,
                'message' => 'Plano obtenido exitosamente',
                'plano' => $plano,
                'status_code' => 200
            ];
        } catch (ModelNotFoundException $e) {
            return [
                'status' => false,
                'message' => 'Plano no encontrado',
                'status_code' => 404
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Error al obtener plano',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function crearPlano(array $datos)
    {
        DB::beginTransaction();
        try {
            // Validar que las zonas existan si se proporcionan
            if (isset($datos['zonas']) && !empty($datos['zonas'])) {
                $zonasExistentes = Zona::whereIn('id', $datos['zonas'])->pluck('id')->toArray();
                $zonasInvalidas = array_diff($datos['zonas'], $zonasExistentes);
                
                if (!empty($zonasInvalidas)) {
                    return [
                        'status' => false,
                        'message' => 'Las siguientes zonas no existen: ' . implode(', ', $zonasInvalidas),
                        'status_code' => 422
                    ];
                }
            }

            // Crear el plano
            $plano = Plano::create([
                'version' => $datos['version'] ?? '1.0',
                'fecha_actualizacion' => $datos['fecha_actualizacion'] ?? now()->toDateString(),
                'zonas' => $datos['zonas'] ?? [],
                'descripcion' => $datos['descripcion'] ?? null,
                'activo' => $datos['activo'] ?? false
            ]);

            // Si se marca como activo, desactivar otros planos
            if ($plano->activo) {
                $this->desactivarOtrosPlanos($plano->id);
            }

            DB::commit();
            
            $plano->zonas_relacionadas = $plano->getZonasRelacionadas();
            
            return [
                'status' => true,
                'message' => 'Plano creado exitosamente',
                'plano' => $plano,
                'status_code' => 201
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'status' => false,
                'message' => 'Error al crear plano',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function actualizarPlano($planoId, array $datos)
    {
        DB::beginTransaction();
        try {
            $plano = Plano::findOrFail($planoId);
            
            // Validar que las zonas existan si se proporcionan
            if (isset($datos['zonas']) && !empty($datos['zonas'])) {
                $zonasExistentes = Zona::whereIn('id', $datos['zonas'])->pluck('id')->toArray();
                $zonasInvalidas = array_diff($datos['zonas'], $zonasExistentes);
                
                if (!empty($zonasInvalidas)) {
                    return [
                        'status' => false,
                        'message' => 'Las siguientes zonas no existen: ' . implode(', ', $zonasInvalidas),
                        'status_code' => 422
                    ];
                }
            }

            // Preparar datos de actualización
            $datosActualizacion = [];
            
            $camposPermitidos = ['version', 'fecha_actualizacion', 'zonas', 'descripcion', 'activo'];
            
            foreach ($camposPermitidos as $campo) {
                if (isset($datos[$campo])) {
                    $datosActualizacion[$campo] = $datos[$campo];
                }
            }

            // Actualizar fecha_actualizacion automáticamente
            $datosActualizacion['fecha_actualizacion'] = now()->toDateString();

            $plano->update($datosActualizacion);

            // Si se activa este plano, desactivar otros
            if (isset($datos['activo']) && $datos['activo']) {
                $this->desactivarOtrosPlanos($plano->id);
            }

            DB::commit();
            
            $plano->zonas_relacionadas = $plano->getZonasRelacionadas();
            
            return [
                'status' => true,
                'message' => 'Plano actualizado exitosamente',
                'plano' => $plano,
                'status_code' => 200
            ];
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return [
                'status' => false,
                'message' => 'Plano no encontrado',
                'status_code' => 404
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'status' => false,
                'message' => 'Error al actualizar plano',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function activarPlano($planoId)
    {
        DB::beginTransaction();
        try {
            $plano = Plano::findOrFail($planoId);
            
            // Desactivar otros planos
            $this->desactivarOtrosPlanos($planoId);
            
            // Activar este plano
            $plano->update([
                'activo' => true,
                'fecha_actualizacion' => now()->toDateString()
            ]);
            
            // Activar las zonas asociadas a este plano
            if (!empty($plano->zonas)) {
                Zona::whereIn('id', $plano->zonas)->update(['activa' => true]);
            }
            
            // Desactivar zonas que no están en este plano
            $otrosPlanos = Plano::where('id', '!=', $planoId)->get();
            $zonasOtrosPlanos = [];
            foreach ($otrosPlanos as $otroPlano) {
                $zonasOtrosPlanos = array_merge($zonasOtrosPlanos, $otroPlano->zonas ?? []);
            }
            
            if (!empty($zonasOtrosPlanos)) {
                Zona::whereIn('id', $zonasOtrosPlanos)
                    ->whereNotIn('id', $plano->zonas ?? [])
                    ->update(['activa' => false]);
            }

            DB::commit();
            
            $plano->zonas_relacionadas = $plano->getZonasRelacionadas();
            
            return [
                'status' => true,
                'message' => 'Plano activado exitosamente',
                'plano' => $plano,
                'status_code' => 200
            ];
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return [
                'status' => false,
                'message' => 'Plano no encontrado',
                'status_code' => 404
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'status' => false,
                'message' => 'Error al activar plano',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function obtenerPlanosPorFecha($fechaInicio, $fechaFin)
    {
        try {
            $inicio = Carbon::parse($fechaInicio);
            $fin = Carbon::parse($fechaFin);
            
            if ($inicio->gt($fin)) {
                return [
                    'status' => false,
                    'message' => 'La fecha de inicio debe ser anterior a la fecha de fin',
                    'status_code' => 422
                ];
            }

            $planos = Plano::whereBetween('fecha_actualizacion', [$inicio, $fin])
                ->orderBy('fecha_actualizacion', 'desc')
                ->get();

            $planos->each(function ($plano) {
                $plano->zonas_relacionadas = $plano->getZonasRelacionadas();
            });
            
            return [
                'status' => true,
                'message' => 'Planos por fecha obtenidos exitosamente',
                'planos' => $planos,
                'fecha_inicio' => $inicio->toDateString(),
                'fecha_fin' => $fin->toDateString(),
                'status_code' => 200
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Error al obtener planos por fecha',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function obtenerHistorialPlanos()
    {
        try {
            $planos = Plano::orderBy('fecha_actualizacion', 'desc')
                ->get()
                ->map(function ($plano) {
                    return [
                        'id' => $plano->id,
                        'version' => $plano->version,
                        'fecha_actualizacion' => $plano->fecha_actualizacion,
                        'descripcion' => $plano->descripcion,
                        'activo' => $plano->activo,
                        'cantidad_zonas' => count($plano->zonas ?? []),
                        'created_at' => $plano->created_at,
                        'updated_at' => $plano->updated_at
                    ];
                });
            
            return [
                'status' => true,
                'message' => 'Historial de planos obtenido exitosamente',
                'planos' => $planos,
                'status_code' => 200
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Error al obtener historial de planos',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function duplicarPlano($planoId, array $nuevaData)
    {
        DB::beginTransaction();
        try {
            $planoOriginal = Plano::findOrFail($planoId);
            
            // Crear el nuevo plano
            $nuevoPlano = Plano::create([
                'version' => $nuevaData['version'] ?? ($planoOriginal->version . '.1'),
                'fecha_actualizacion' => now()->toDateString(),
                'zonas' => $nuevaData['duplicar_zonas'] ? $planoOriginal->zonas : [],
                'descripcion' => $nuevaData['descripcion'] ?? $planoOriginal->descripcion . ' (Copia)',
                'activo' => false // Por defecto inactivo
            ]);

            DB::commit();
            
            $nuevoPlano->zonas_relacionadas = $nuevoPlano->getZonasRelacionadas();
            $planoOriginal->zonas_relacionadas = $planoOriginal->getZonasRelacionadas();
            
            return [
                'status' => true,
                'message' => 'Plano duplicado exitosamente',
                'plano_original' => $planoOriginal,
                'plano_nuevo' => $nuevoPlano,
                'status_code' => 201
            ];
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return [
                'status' => false,
                'message' => 'Plano original no encontrado',
                'status_code' => 404
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'status' => false,
                'message' => 'Error al duplicar plano',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    /**
     * Desactivar otros planos excepto el especificado
     */
    private function desactivarOtrosPlanos($planoIdExcluido)
    {
        Plano::where('id', '!=', $planoIdExcluido)
            ->where('activo', true)
            ->update(['activo' => false]);
    }
}