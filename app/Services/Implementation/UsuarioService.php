<?php

namespace App\Services\Implementation;

use App\Models\Usuario;
use App\Services\Interface\UsuarioServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UsuarioService implements UsuarioServiceInterface
{
    public function obtenerTodosLosUsuarios()
    {
        try {
            $usuarios = Usuario::with(['vehiculos', 'estacionamientos'])->get();
            
            return [
                'status' => true,
                'message' => 'Usuarios obtenidos exitosamente',
                'usuarios' => $usuarios,
                'status_code' => 200
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Error al obtener usuarios',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function obtenerUsuario($usuarioId)
    {
        try {
            $usuario = Usuario::with(['vehiculos', 'estacionamientos'])->findOrFail($usuarioId);
            
            return [
                'status' => true,
                'message' => 'Usuario obtenido exitosamente',
                'usuario' => $usuario,
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
                'message' => 'Error al obtener usuario',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function crearUsuario(array $datos)
    {
        DB::beginTransaction();
        try {
            // Verificar si el email ya existe
            $usuarioExistente = Usuario::where('email', $datos['email'])->first();
            if ($usuarioExistente) {
                return [
                    'status' => false,
                    'message' => 'Ya existe un usuario con ese email',
                    'status_code' => 409
                ];
            }

            $usuario = Usuario::create([
                'nombre' => $datos['nombre'],
                'email' => $datos['email'],
                'telefono' => $datos['telefono'] ?? null,
                'password' => Hash::make($datos['password']),
            ]);

            DB::commit();
            
            return [
                'status' => true,
                'message' => 'Usuario creado exitosamente',
                'usuario' => $usuario,
                'status_code' => 201
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'status' => false,
                'message' => 'Error al crear usuario',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function actualizarUsuario($usuarioId, array $datos)
    {
        DB::beginTransaction();
        try {
            $usuario = Usuario::findOrFail($usuarioId);
            
            // Verificar si el email ya existe (excluyendo el usuario actual)
            if (isset($datos['email'])) {
                $usuarioExistente = Usuario::where('email', $datos['email'])
                    ->where('id', '!=', $usuarioId)
                    ->first();
                    
                if ($usuarioExistente) {
                    return [
                        'status' => false,
                        'message' => 'Ya existe un usuario con ese email',
                        'status_code' => 409
                    ];
                }
            }

            // Preparar datos de actualización
            $datosActualizacion = [];
            $camposPermitidos = ['nombre', 'email', 'telefono'];
            
            foreach ($camposPermitidos as $campo) {
                if (isset($datos[$campo])) {
                    $datosActualizacion[$campo] = $datos[$campo];
                }
            }

            // Si hay una nueva contraseña, hashearla
            if (isset($datos['password'])) {
                $datosActualizacion['password'] = Hash::make($datos['password']);
            }

            $usuario->update($datosActualizacion);

            DB::commit();
            
            return [
                'status' => true,
                'message' => 'Usuario actualizado exitosamente',
                'usuario' => $usuario->fresh(),
                'status_code' => 200
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
                'message' => 'Error al actualizar usuario',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function eliminarUsuario($usuarioId)
    {
        DB::beginTransaction();
        try {
            $usuario = Usuario::findOrFail($usuarioId);
            
            // Verificar que no tenga estacionamientos activos
            $estacionamientoActivo = $usuario->estacionamientos()
                ->where('estado', 'activo')
                ->first();
                
            if ($estacionamientoActivo) {
                return [
                    'status' => false,
                    'message' => 'No se puede eliminar el usuario porque tiene estacionamientos activos',
                    'status_code' => 409
                ];
            }

            $usuario->delete();
            DB::commit();
            
            return [
                'status' => true,
                'message' => 'Usuario eliminado exitosamente',
                'status_code' => 200
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
                'message' => 'Error al eliminar usuario',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function cambiarPassword($usuarioId, $passwordActual, $passwordNueva)
    {
        DB::beginTransaction();
        try {
            $usuario = Usuario::findOrFail($usuarioId);
            
            // Verificar password actual
            if (!Hash::check($passwordActual, $usuario->password)) {
                return [
                    'status' => false,
                    'message' => 'La contraseña actual es incorrecta',
                    'status_code' => 401
                ];
            }

            $usuario->update([
                'password' => Hash::make($passwordNueva)
            ]);

            DB::commit();
            
            return [
                'status' => true,
                'message' => 'Contraseña actualizada exitosamente',
                'status_code' => 200
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
                'message' => 'Error al cambiar contraseña',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function actualizarPerfil($usuarioId, array $datos)
    {
        return $this->actualizarUsuario($usuarioId, $datos);
    }

    public function buscarUsuarios($criterio)
    {
        try {
            $usuarios = Usuario::where('nombre', 'LIKE', "%{$criterio}%")
                ->orWhere('email', 'LIKE', "%{$criterio}%")
                ->orWhere('telefono', 'LIKE', "%{$criterio}%")
                ->with(['vehiculos', 'estacionamientos'])
                ->get();
            
            return [
                'status' => true,
                'message' => 'Búsqueda realizada exitosamente',
                'usuarios' => $usuarios,
                'criterio' => $criterio,
                'status_code' => 200
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Error al buscar usuarios',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }
}