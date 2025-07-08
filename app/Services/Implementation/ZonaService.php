<?php

namespace App\Services\Implementation;

use App\Models\Zona;
use App\Services\Interface\ZonaServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ZonaService implements ZonaServiceInterface
{
    public function obtenerTodasLasZonas()
    {
        try {
            $zonas = Zona::with('estacionamientos')->orderBy('nombre')->get();
            
            return [
                'status' => true,
                'message' => 'Zonas obtenidas exitosamente',
                'zonas' => $zonas,
                'status_code' => 200
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Error al obtener zonas',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function obtenerZonasActivas()
    {
        try {
            $zonas = Zona::where('activa', true)
                ->with('estacionamientos')
                ->orderBy('nombre')
                ->get();
            
            return [
                'status' => true,
                'message' => 'Zonas activas obtenidas exitosamente',
                'zonas' => $zonas,
                'status_code' => 200
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Error al obtener zonas activas',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function obtenerZona($zonaId)
    {
        try {
            $zona = Zona::with(['estacionamientos' => function ($query) {
                $query->where('estado', 'activo');
            }])->findOrFail($zonaId);
            
            return [
                'status' => true,
                'message' => 'Zona obtenida exitosamente',
                'zona' => $zona,
                'status_code' => 200
            ];
        } catch (ModelNotFoundException $e) {
            return [
                'status' => false,
                'message' => 'Zona no encontrada',
                'status_code' => 404
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Error al obtener zona',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function crearZona(array $datos)
    {
        DB::beginTransaction();
        try {
            // Verificar que no exista una zona con el mismo nombre
            $zonaExistente = Zona::where('nombre', $datos['nombre'])->first();
            if ($zonaExistente) {
                return [
                    'status' => false,
                    'message' => 'Ya existe una zona con ese nombre',
                    'status_code' => 409
                ];
            }

            // Validar coordenadas del polígono si se proporcionan
            if (isset($datos['poligono_coordenadas']) && !empty($datos['poligono_coordenadas'])) {
                if (!$this->validarPoligono($datos['poligono_coordenadas'])) {
                    return [
                        'status' => false,
                        'message' => 'Las coordenadas del polígono no son válidas',
                        'status_code' => 422
                    ];
                }
            }

            $zona = Zona::create([
                'nombre' => $datos['nombre'],
                'descripcion' => $datos['descripcion'] ?? null,
                'color_mapa' => $datos['color_mapa'] ?? '#FF0000',
                'poligono_coordenadas' => $datos['poligono_coordenadas'] ?? [],
                'hora_inicio' => $datos['hora_inicio'] ?? '08:00:00',
                'hora_fin' => $datos['hora_fin'] ?? '20:00:00',
                'dias_habilitados' => $datos['dias_habilitados'] ?? ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'],
                'tarifa_por_hora' => $datos['tarifa_por_hora'] ?? 0,
                'es_prohibido_estacionar' => $datos['es_prohibido_estacionar'] ?? false,
                'activa' => $datos['activa'] ?? true
            ]);

            DB::commit();
            
            return [
                'status' => true,
                'message' => 'Zona creada exitosamente',
                'zona' => $zona,
                'status_code' => 201
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'status' => false,
                'message' => 'Error al crear zona',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function actualizarZona($zonaId, array $datos)
    {
        DB::beginTransaction();
        try {
            $zona = Zona::findOrFail($zonaId);
            
            // Verificar nombre único si se está cambiando
            if (isset($datos['nombre']) && $datos['nombre'] !== $zona->nombre) {
                $zonaExistente = Zona::where('nombre', $datos['nombre'])
                    ->where('id', '!=', $zonaId)
                    ->first();
                    
                if ($zonaExistente) {
                    return [
                        'status' => false,
                        'message' => 'Ya existe una zona con ese nombre',
                        'status_code' => 409
                    ];
                }
            }

            // Validar coordenadas del polígono si se proporcionan
            if (isset($datos['poligono_coordenadas']) && !empty($datos['poligono_coordenadas'])) {
                if (!$this->validarPoligono($datos['poligono_coordenadas'])) {
                    return [
                        'status' => false,
                        'message' => 'Las coordenadas del polígono no son válidas',
                        'status_code' => 422
                    ];
                }
            }

            // Preparar datos de actualización
            $datosActualizacion = [];
            
            $camposPermitidos = [
                'nombre', 'descripcion', 'color_mapa', 'poligono_coordenadas',
                'hora_inicio', 'hora_fin', 'dias_habilitados', 'tarifa_por_hora', 
                'es_prohibido_estacionar', 'activa'
            ];
            
            foreach ($camposPermitidos as $campo) {
                if (isset($datos[$campo])) {
                    $datosActualizacion[$campo] = $datos[$campo];
                }
            }

            $zona->update($datosActualizacion);

            DB::commit();
            
            return [
                'status' => true,
                'message' => 'Zona actualizada exitosamente',
                'zona' => $zona->fresh(),
                'status_code' => 200
            ];
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return [
                'status' => false,
                'message' => 'Zona no encontrada',
                'status_code' => 404
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'status' => false,
                'message' => 'Error al actualizar zona',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function activarDesactivarZona($zonaId, $activa)
    {
        try {
            $zona = Zona::findOrFail($zonaId);
            
            $zona->update(['activa' => $activa]);
            
            $mensaje = $activa ? 'Zona activada exitosamente' : 'Zona desactivada exitosamente';
            
            return [
                'status' => true,
                'message' => $mensaje,
                'zona' => $zona,
                'status_code' => 200
            ];
        } catch (ModelNotFoundException $e) {
            return [
                'status' => false,
                'message' => 'Zona no encontrada',
                'status_code' => 404
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Error al cambiar estado de zona',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function obtenerZonasPorTipo($tipo)
    {
        try {
            $query = Zona::where('activa', true);
            
            if ($tipo === 'pago') {
                $query->where('es_prohibido_estacionar', false)
                      ->where('tarifa_por_hora', '>', 0);
            } elseif ($tipo === 'libre') {
                $query->where('es_prohibido_estacionar', false)
                      ->where('tarifa_por_hora', 0);
            } elseif ($tipo === 'prohibido') {
                $query->where('es_prohibido_estacionar', true);
            }
            
            $zonas = $query->orderBy('nombre')->get();
            
            return [
                'status' => true,
                'message' => "Zonas de tipo '{$tipo}' obtenidas exitosamente",
                'zonas' => $zonas,
                'tipo' => $tipo,
                'status_code' => 200
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Error al obtener zonas por tipo',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function verificarPuntoEnZona($latitud, $longitud, $zonaId)
    {
        try {
            $zona = Zona::findOrFail($zonaId);
            
            $estaEnZona = $this->puntoEnPoligono($latitud, $longitud, $zona->poligono_coordenadas);
            
            return [
                'status' => true,
                'message' => $estaEnZona ? 'El punto está dentro de la zona' : 'El punto está fuera de la zona',
                'esta_en_zona' => $estaEnZona,
                'zona' => $zona,
                'coordenadas' => [
                    'latitud' => $latitud,
                    'longitud' => $longitud
                ],
                'status_code' => 200
            ];
        } catch (ModelNotFoundException $e) {
            return [
                'status' => false,
                'message' => 'Zona no encontrada',
                'status_code' => 404
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Error al verificar punto en zona',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function obtenerZonasCercanas($latitud, $longitud, $radio = 0.01)
    {
        try {
            $zonas = Zona::where('activa', true)->get();
            $zonasCercanas = [];
            
            foreach ($zonas as $zona) {
                if ($this->zonaEstaEnRango($latitud, $longitud, $zona, $radio)) {
                    $zonasCercanas[] = $zona;
                }
            }
            
            return [
                'status' => true,
                'message' => 'Zonas cercanas obtenidas exitosamente',
                'zonas' => $zonasCercanas,
                'coordenadas' => [
                    'latitud' => $latitud,
                    'longitud' => $longitud
                ],
                'radio' => $radio,
                'status_code' => 200
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Error al obtener zonas cercanas',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    private function validarPoligono($poligono)
    {
        if (!is_array($poligono) || empty($poligono)) {
            return false;
        }
        
        foreach ($poligono as $punto) {
            if (!isset($punto['lat']) || !isset($punto['lng'])) {
                return false;
            }
            
            if (!is_numeric($punto['lat']) || !is_numeric($punto['lng'])) {
                return false;
            }
            
            if ($punto['lat'] < -90 || $punto['lat'] > 90) {
                return false;
            }
            
            if ($punto['lng'] < -180 || $punto['lng'] > 180) {
                return false;
            }
        }
        
        return count($poligono) >= 3;
    }

    private function puntoEnPoligono($lat, $lng, $poligono)
    {
        if (empty($poligono) || !is_array($poligono)) {
            return false;
        }
        
        $vertices = count($poligono);
        $dentro = false;
        
        for ($i = 0, $j = $vertices - 1; $i < $vertices; $j = $i++) {
            $xi = $poligono[$i]['lat'];
            $yi = $poligono[$i]['lng'];
            $xj = $poligono[$j]['lat'];
            $yj = $poligono[$j]['lng'];
            
            if ((($yi > $lng) != ($yj > $lng)) &&
                ($lat < ($xj - $xi) * ($lng - $yi) / ($yj - $yi) + $xi)) {
                $dentro = !$dentro;
            }
        }
        
        return $dentro;
    }

    private function zonaEstaEnRango($lat, $lng, $zona, $radio)
    {
        if (empty($zona->poligono_coordenadas)) {
            return false;
        }
        
        if ($this->puntoEnPoligono($lat, $lng, $zona->poligono_coordenadas)) {
            return true;
        }
        
        $centroide = $this->calcularCentroide($zona->poligono_coordenadas);
        if ($centroide) {
            $distancia = sqrt(
                pow($lat - $centroide['lat'], 2) + 
                pow($lng - $centroide['lng'], 2)
            );
            
            return $distancia <= $radio;
        }
        
        return false;
    }

    private function calcularCentroide($poligono)
    {
        if (empty($poligono)) {
            return null;
        }
        
        $latTotal = 0;
        $lngTotal = 0;
        $puntos = count($poligono);
        
        foreach ($poligono as $punto) {
            $latTotal += $punto['lat'];
            $lngTotal += $punto['lng'];
        }
        
        return [
            'lat' => $latTotal / $puntos,
            'lng' => $lngTotal / $puntos
        ];
    }
}