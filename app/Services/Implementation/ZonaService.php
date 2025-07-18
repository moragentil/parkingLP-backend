<?php

namespace App\Services\Implementation;

use App\Models\Zona;
use App\Models\TarifaHorario;
use App\Services\Interface\ZonaServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ZonaService implements ZonaServiceInterface
{
    public function obtenerTodasLasZonas()
    {
        try {
            $zonas = Zona::with(['estacionamientos', 'horarios'])
                ->orderBy('nombre')
                ->get()
                ->map(function ($zona) {
                    return $this->formatearZonaConTarifas($zona);
                });
            
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
                ->with(['estacionamientos', 'horarios'])
                ->orderBy('nombre')
                ->get()
                ->map(function ($zona) {
                    return $this->formatearZonaConTarifas($zona);
                });
            
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
            $zona = Zona::with([
                'estacionamientos' => function ($query) {
                    $query->where('estado', 'activo');
                },
                'horarios'
            ])->findOrFail($zonaId);
            
            $zonaFormateada = $this->formatearZonaConTarifas($zona);
            
            return [
                'status' => true,
                'message' => 'Zona obtenida exitosamente',
                'zona' => $zonaFormateada,
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
                'es_prohibido_estacionar' => $datos['es_prohibido_estacionar'] ?? false,
                'activa' => $datos['activa'] ?? true
            ]);

            DB::commit();
            
            $zonaFormateada = $this->formatearZonaConTarifas($zona);
            
            return [
                'status' => true,
                'message' => 'Zona creada exitosamente',
                'zona' => $zonaFormateada,
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

            // Preparar datos de actualización (sin campos eliminados)
            $datosActualizacion = [];
            
            $camposPermitidos = [
                'nombre', 'descripcion', 'color_mapa', 'poligono_coordenadas',
                'es_prohibido_estacionar', 'activa'
            ];
            
            foreach ($camposPermitidos as $campo) {
                if (isset($datos[$campo])) {
                    $datosActualizacion[$campo] = $datos[$campo];
                }
            }

            $zona->update($datosActualizacion);

            DB::commit();
            
            $zonaFormateada = $this->formatearZonaConTarifas($zona->fresh(['horarios']));
            
            return [
                'status' => true,
                'message' => 'Zona actualizada exitosamente',
                'zona' => $zonaFormateada,
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
            
            $zonaFormateada = $this->formatearZonaConTarifas($zona->load('horarios'));
            
            return [
                'status' => true,
                'message' => $mensaje,
                'zona' => $zonaFormateada,
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
                $query->where('es_prohibido_estacionar', false);
            } elseif ($tipo === 'libre') {
                $query->where('es_prohibido_estacionar', false);
            } elseif ($tipo === 'prohibido') {
                $query->where('es_prohibido_estacionar', true);
            }
            
            $zonas = $query->with(['horarios'])
                ->orderBy('nombre')
                ->get()
                ->map(function ($zona) {
                    return $this->formatearZonaConTarifas($zona);
                })
                ->filter(function ($zona) use ($tipo) {
                    // Filtrar por tipo después de formatear
                    return $zona['tipo'] === $tipo;
                })
                ->values(); // Reindexar el array
            
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
            $zona = Zona::with('horarios')->findOrFail($zonaId);
            
            $estaEnZona = $this->puntoEnPoligono($latitud, $longitud, $zona->poligono_coordenadas);
            
            $zonaFormateada = $this->formatearZonaConTarifas($zona);
            
            return [
                'status' => true,
                'message' => $estaEnZona ? 'El punto está dentro de la zona' : 'El punto está fuera de la zona',
                'esta_en_zona' => $estaEnZona,
                'zona' => $zonaFormateada,
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
            $zonas = Zona::where('activa', true)->with('horarios')->get();
            $zonasCercanas = [];
            
            foreach ($zonas as $zona) {
                if ($this->zonaEstaEnRango($latitud, $longitud, $zona, $radio)) {
                    $zonasCercanas[] = $this->formatearZonaConTarifas($zona);
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

    /**
     * Formatear zona con horarios y tarifas dinámicas
     */
    private function formatearZonaConTarifas($zona)
    {
        // Obtener todas las tarifas disponibles con manejo de errores
        try {
            $tarifasDisponibles = TarifaHorario::where('activa', true)
                ->orderBy('hora_inicio')
                ->get()
                ->map(function ($tarifa) {
                    try {
                        return [
                            'id' => $tarifa->id,
                            'nombre' => $tarifa->nombre,
                            'hora_inicio' => $tarifa->hora_inicio ? $tarifa->hora_inicio->format('H:i') : '00:00',
                            'hora_fin' => $tarifa->hora_fin ? $tarifa->hora_fin->format('H:i') : '23:59',
                            'precio_por_hora' => (float) $tarifa->precio_por_hora,
                            'descripcion' => $tarifa->descripcion
                        ];
                    } catch (\Exception $e) {
                        // Si hay error al formatear una tarifa, usar valores por defecto
                        return [
                            'id' => $tarifa->id,
                            'nombre' => $tarifa->nombre ?? 'Tarifa',
                            'hora_inicio' => '00:00',
                            'hora_fin' => '23:59',
                            'precio_por_hora' => (float) ($tarifa->precio_por_hora ?? 0),
                            'descripcion' => $tarifa->descripcion ?? ''
                        ];
                    }
                });
        } catch (\Exception $e) {
            // Si hay error con las tarifas, usar array vacío
            $tarifasDisponibles = collect([]);
        }

        // Formatear horarios por día con manejo de errores
        try {
            $horariosPorDia = $zona->horarios->groupBy('dia_semana')->map(function ($horarios, $dia) {
                return $horarios->map(function ($horario) {
                    try {
                        return [
                            'id' => $horario->id,
                            'hora_inicio' => $horario->hora_inicio ? $horario->hora_inicio->format('H:i') : '00:00',
                            'hora_fin' => $horario->hora_fin ? $horario->hora_fin->format('H:i') : '23:59',
                            'activo' => $horario->activo ?? true
                        ];
                    } catch (\Exception $e) {
                        // Si hay error al formatear un horario, usar valores por defecto
                        return [
                            'id' => $horario->id,
                            'hora_inicio' => '00:00',
                            'hora_fin' => '23:59',
                            'activo' => true
                        ];
                    }
                })->first(); // Asumiendo un horario por día
            });
        } catch (\Exception $e) {
            $horariosPorDia = collect([]);
        }

        // Determinar tipo de zona
        $tipo = 'libre'; // Por defecto
        if ($zona->es_prohibido_estacionar) {
            $tipo = 'prohibida';
        } elseif ($zona->horarios->isNotEmpty() && !$zona->es_prohibido_estacionar) {
            $tipo = 'paga'; // Si tiene horarios y no es prohibida, es de pago
        }

        // Calcular tarifa actual (basada en la hora actual) con manejo de errores
        $horaActual = now()->format('H:i:s');
        $tarifaActual = null;
        if ($tipo === 'paga') {
            try {
                $tarifa = TarifaHorario::obtenerTarifaPorHora($horaActual);
                if ($tarifa) {
                    $tarifaActual = [
                        'precio_por_hora' => (float) $tarifa->precio_por_hora,
                        'nombre' => $tarifa->nombre,
                        'descripcion' => $tarifa->descripcion
                    ];
                }
            } catch (\Exception $e) {
                // Si hay error al obtener tarifa, usar null
                $tarifaActual = null;
            }
        }

        return [
            'id' => $zona->id,
            'nombre' => $zona->nombre,
            'descripcion' => $zona->descripcion,
            'color_mapa' => $zona->color_mapa,
            'poligono_coordenadas' => $zona->poligono_coordenadas,
            'es_prohibido_estacionar' => $zona->es_prohibido_estacionar,
            'activa' => $zona->activa,
            'tipo' => $tipo,
            'horarios_por_dia' => $horariosPorDia,
            'tarifas_disponibles' => $tarifasDisponibles,
            'tarifa_actual' => $tarifaActual,
            'estacionamientos' => $zona->estacionamientos ?? [],
            'created_at' => $zona->created_at,
            'updated_at' => $zona->updated_at
        ];
    }

    /**
     * Obtener tarifas horarias disponibles
     */
    public function obtenerTarifasHorarias()
    {
        try {
            $tarifas = TarifaHorario::where('activa', true)
                ->orderBy('hora_inicio')
                ->get();
            
            return [
                'status' => true,
                'message' => 'Tarifas horarias obtenidas exitosamente',
                'tarifas' => $tarifas,
                'status_code' => 200
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Error al obtener tarifas horarias',
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    private function puntoEnPoligono($lat, $lng, $poligonos)
    {
        if (empty($poligonos) || !is_array($poligonos)) {
            return false;
        }

        // Si es un polígono simple (array de coordenadas), convertir a array de polígonos
        if (isset($poligonos[0]['lat'])) {
            $poligonos = [$poligonos];
        }

        // Verificar en cada polígono
        foreach ($poligonos as $poligono) {
            if ($this->puntoEnPoligonoSimple($lat, $lng, $poligono)) {
                return true;
            }
        }

        return false;
    }

    private function puntoEnPoligonoSimple($lat, $lng, $poligono)
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

    private function calcularCentroide($poligonos)
    {
        if (empty($poligonos)) {
            return null;
        }

        // Si es un polígono simple, convertir a array de polígonos
        if (isset($poligonos[0]['lat'])) {
            $poligonos = [$poligonos];
        }

        $latTotal = 0;
        $lngTotal = 0;
        $puntosTotal = 0;

        foreach ($poligonos as $poligono) {
            foreach ($poligono as $punto) {
                $latTotal += $punto['lat'];
                $lngTotal += $punto['lng'];
                $puntosTotal++;
            }
        }

        if ($puntosTotal === 0) {
            return null;
        }

        return [
            'lat' => $latTotal / $puntosTotal,
            'lng' => $lngTotal / $puntosTotal
        ];
    }

    private function validarPoligono($poligonos)
    {
        if (!is_array($poligonos) || empty($poligonos)) {
            return false;
        }

        // Si es un polígono simple, convertir a array de polígonos
        if (isset($poligonos[0]['lat'])) {
            $poligonos = [$poligonos];
        }

        foreach ($poligonos as $poligono) {
            if (!is_array($poligono) || count($poligono) < 3) {
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
        }
        
        return true;
    }

    public function obtenerLeyendaZonas()
{
    try {
        $zonas = $this->obtenerZonasActivas();
        
        if (!$zonas['status']) {
            return $zonas; // Retornar directamente el array, no response()->json()
        }
        
        // Formatear datos específicamente para la leyenda del mapa
        $leyenda = collect($zonas['zonas'])->map(function ($zona) {
            return [
                'id' => $zona['id'],
                'nombre' => $zona['nombre'],
                'color_mapa' => $zona['color_mapa'],
                'tipo' => $zona['tipo'],
                'es_prohibido_estacionar' => $zona['es_prohibido_estacionar'],
                'horarios_formateados' => $this->formatearHorariosParaLeyenda($zona['horarios_por_dia']),
                'tarifas_formateadas' => $this->formatearTarifasParaLeyenda($zona['tarifas_disponibles'])
            ];
        });
        
        return [
            'status' => true,
            'message' => 'Leyenda de zonas obtenida exitosamente',
            'leyenda' => $leyenda,
            'status_code' => 200
        ];
        
    } catch (\Exception $e) {
        return [
            'status' => false,
            'message' => 'Error al obtener leyenda de zonas',
            'error' => $e->getMessage(),
            'status_code' => 500
        ];
    }
}

private function formatearHorariosParaLeyenda($horariosPorDia)
{
    $horarios = [];
    
    // Convertir a array si es collection
    if (is_object($horariosPorDia)) {
        $horariosPorDia = $horariosPorDia->toArray();
    }
    
    // Lunes a Viernes
    $lunesViernes = [];
    $diasSemana = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];
    
    foreach ($diasSemana as $dia) {
        if (isset($horariosPorDia[$dia])) {
            $horario = $horariosPorDia[$dia];
            $lunesViernes[] = $horario['hora_inicio'] . ' - ' . $horario['hora_fin'];
        }
    }
    
    if (!empty($lunesViernes)) {
        $horarios[] = 'Lun-Vie: ' . implode(', ', array_unique($lunesViernes));
    }
    
    // Sábados
    if (isset($horariosPorDia['sabado'])) {
        $sabado = $horariosPorDia['sabado'];
        $horarios[] = 'Sáb: ' . $sabado['hora_inicio'] . ' - ' . $sabado['hora_fin'];
    }
    
    // Domingos
    if (isset($horariosPorDia['domingo'])) {
        $domingo = $horariosPorDia['domingo'];
        $horarios[] = 'Dom: ' . $domingo['hora_inicio'] . ' - ' . $domingo['hora_fin'];
    }
    
    return empty($horarios) ? ['Sin horarios definidos'] : $horarios;
}

private function formatearTarifasParaLeyenda($tarifas)
{
    if (empty($tarifas)) {
        return 'Gratuito';
    }
    
    // Convertir a collection si es array
    $tarifasCollection = collect($tarifas);
    $precios = $tarifasCollection->pluck('precio_por_hora')->unique()->sort()->values();
    
    if ($precios->count() === 1) {
        return '$' . number_format($precios->first(), 0);
    }
    
    return '$' . number_format($precios->min(), 0) . ' - $' . number_format($precios->max(), 0);
}
}