<?php

namespace App\Services\Implementation;

use App\Models\Vehiculo;
use App\Models\Usuario;
use App\Services\Interface\VehiculoServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class VehiculoService implements VehiculoServiceInterface
{
    public function obtenerVehiculosUsuario($usuarioId)
    {
        try {
            $usuario = Usuario::findOrFail($usuarioId);
            $vehiculos = $usuario->vehiculos()->with('estacionamientos')->get();
            
            return [
                'status' => true,
                'message' => 'Vehículos obtenidos exitosamente',
                'vehiculos' => $vehiculos,
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
                'message' => 'Error al obtener vehículos',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function crearVehiculo($usuarioId, array $datos)
    {
        DB::beginTransaction();
        try {
            // Verificar si el usuario existe
            $usuario = Usuario::findOrFail($usuarioId);
            
            // Verificar si la patente ya existe
            $vehiculoExistente = Vehiculo::where('patente', strtoupper($datos['patente']))->first();
            if ($vehiculoExistente) {
                return [
                    'status' => false,
                    'message' => 'Ya existe un vehículo con esa patente',
                    'status_code' => 409
                ];
            }

            // Crear el vehículo usando los campos que existen en la migración
            $vehiculo = Vehiculo::create([
                'usuario_id' => $usuarioId,
                'patente' => strtoupper($datos['patente']),
                'activo' => true
            ]);

            DB::commit();
            
            return [
                'status' => true,
                'message' => 'Vehículo registrado exitosamente',
                'vehiculo' => $vehiculo,
                'status_code' => 201
            ];
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return [
                'status' => false,
                'message' => 'Usuario no encontrado',
                'status_code' => 404
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'status' => false,
                'message' => 'Error al registrar vehículo',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function obtenerVehiculo($usuarioId, $vehiculoId)
    {
        try {
            $usuario = Usuario::findOrFail($usuarioId);
            $vehiculo = $usuario->vehiculos()->with('estacionamientos')->findOrFail($vehiculoId);
            
            return [
                'status' => true,
                'message' => 'Vehículo obtenido exitosamente',
                'vehiculo' => $vehiculo,
                'status_code' => 200
            ];
        } catch (ModelNotFoundException $e) {
            return [
                'status' => false,
                'message' => 'Vehículo no encontrado',
                'status_code' => 404
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Error al obtener vehículo',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function actualizarVehiculo($usuarioId, $vehiculoId, array $datos)
    {
        DB::beginTransaction();
        try {
            $usuario = Usuario::findOrFail($usuarioId);
            $vehiculo = $usuario->vehiculos()->findOrFail($vehiculoId);
            
            // Verificar si la patente ya existe (excluyendo el vehículo actual)
            if (isset($datos['patente'])) {
                $vehiculoExistente = Vehiculo::where('patente', strtoupper($datos['patente']))
                    ->where('id', '!=', $vehiculoId)
                    ->first();
                    
                if ($vehiculoExistente) {
                    return [
                        'status' => false,
                        'message' => 'Ya existe un vehículo con esa patente',
                        'status_code' => 409
                    ];
                }
            }

            // Preparar datos de actualización usando solo los campos disponibles
            $datosActualizacion = [];
            
            if (isset($datos['patente'])) {
                $datosActualizacion['patente'] = strtoupper($datos['patente']);
            }
            
            if (isset($datos['ubicacion_actual_lat'])) {
                $datosActualizacion['ubicacion_actual_lat'] = $datos['ubicacion_actual_lat'];
            }
            
            if (isset($datos['ubicacion_actual_lng'])) {
                $datosActualizacion['ubicacion_actual_lng'] = $datos['ubicacion_actual_lng'];
            }
            
            if (isset($datos['fecha_estacionamiento'])) {
                $datosActualizacion['fecha_estacionamiento'] = $datos['fecha_estacionamiento'];
            }
            
            if (isset($datos['hora_estacionamiento'])) {
                $datosActualizacion['hora_estacionamiento'] = $datos['hora_estacionamiento'];
            }
            
            if (isset($datos['activo'])) {
                $datosActualizacion['activo'] = $datos['activo'];
            }

            // Actualizar el vehículo
            $vehiculo->update($datosActualizacion);

            DB::commit();
            
            return [
                'status' => true,
                'message' => 'Vehículo actualizado exitosamente',
                'vehiculo' => $vehiculo->fresh(),
                'status_code' => 200
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
                'message' => 'Error al actualizar vehículo',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function eliminarVehiculo($usuarioId, $vehiculoId)
    {
        DB::beginTransaction();
        try {
            $usuario = Usuario::findOrFail($usuarioId);
            $vehiculo = $usuario->vehiculos()->findOrFail($vehiculoId);
            
            // Verificar que no tenga estacionamientos activos
            $estacionamientoActivo = $vehiculo->estacionamientos()
                ->where('estado', 'activo')
                ->first();
            
            if ($estacionamientoActivo) {
                return [
                    'status' => false,
                    'message' => 'No se puede eliminar un vehículo con estacionamiento activo',
                    'status_code' => 409
                ];
            }

            $vehiculo->delete();
            DB::commit();
            
            return [
                'status' => true,
                'message' => 'Vehículo eliminado exitosamente',
                'status_code' => 200
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
                'message' => 'Error al eliminar vehículo',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function obtenerVehiculoActivo($usuarioId)
    {
        try {
            $usuario = Usuario::findOrFail($usuarioId);
            
            // Buscar vehículo que tenga estacionamiento activo
            $vehiculo = $usuario->vehiculos()
                ->whereHas('estacionamientos', function ($query) {
                    $query->where('estado', 'activo');
                })
                ->with(['estacionamientos' => function ($query) {
                    $query->where('estado', 'activo')
                          ->with(['zona', 'alarma']);
                }])
                ->first();
            
            if (!$vehiculo) {
                return [
                    'status' => false,
                    'message' => 'No hay vehículo con estacionamiento activo',
                    'status_code' => 404
                ];
            }

            return [
                'status' => true,
                'message' => 'Vehículo activo obtenido exitosamente',
                'vehiculo' => $vehiculo,
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
                'message' => 'Error al obtener vehículo activo',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    /**
     * Actualizar ubicación actual del vehículo
     */
    public function actualizarUbicacion($usuarioId, $vehiculoId, $latitud, $longitud)
    {
        try {
            $usuario = Usuario::findOrFail($usuarioId);
            $vehiculo = $usuario->vehiculos()->findOrFail($vehiculoId);
            
            $vehiculo->update([
                'ubicacion_actual_lat' => $latitud,
                'ubicacion_actual_lng' => $longitud
            ]);
            
            return [
                'status' => true,
                'message' => 'Ubicación actualizada exitosamente',
                'vehiculo' => $vehiculo->fresh(),
                'status_code' => 200
            ];
        } catch (ModelNotFoundException $e) {
            return [
                'status' => false,
                'message' => 'Vehículo no encontrado',
                'status_code' => 404
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Error al actualizar ubicación',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }
}