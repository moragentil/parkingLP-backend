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
                            'hora_inicio' => $tarifa->hora_inicio ? $tarifa->hora_inicio : '00:00',
                            'hora_fin' => $tarifa->hora_fin ? $tarifa->hora_fin : '23:59',
                            'precio_por_hora' => (float) $tarifa->precio_por_hora,
                            'descripcion' => $tarifa->descripcion
                        ];
                    } catch (\Exception $e) {
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
            $tarifasDisponibles = collect([]);
        }

        // Formatear horarios por día con manejo de errores
        try {
            $horariosPorDia = collect([]);
        
            if ($zona->horarios && $zona->horarios->isNotEmpty()) {
                $horariosPorDia = $zona->horarios->groupBy('dia_semana')->map(function ($horarios, $dia) {
                    // Tomar el primer horario del día
                    $horario = $horarios->first();
                
                    try {
                        return [
                            'id' => $horario->id,
                            'hora_inicio' => $horario->hora_inicio ?? '00:00:00',
                            'hora_fin' => $horario->hora_fin ?? '23:59:59',
                            'activo' => $horario->activo ?? true
                        ];
                    } catch (\Exception $e) {
                        return [
                            'id' => $horario->id ?? null,
                            'hora_inicio' => '00:00:00',
                            'hora_fin' => '23:59:59',
                            'activo' => true
                        ];
                    }
                });
            }
        } catch (\Exception $e) {
            $horariosPorDia = collect([]);
        }

        // Determinar tipo de zona
        $tipo = 'libre';
        if ($zona->es_prohibido_estacionar) {
            $tipo = 'prohibida';
        } elseif ($zona->horarios && $zona->horarios->isNotEmpty() && !$zona->es_prohibido_estacionar) {
            $tipo = 'paga';
        }

        // Calcular tarifa actual
        $horaActual = now()->format('H:i:s');
        $tarifaActual = null;
        if ($tipo === 'paga' && $tarifasDisponibles->isNotEmpty()) {
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
            return $zonas;
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
        \Log::error('Error en obtenerLeyendaZonas: ' . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());
        
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
    
    if (empty($horariosPorDia)) {
        return ['Sin horarios definidos'];
    }
    
    // Lunes a Viernes
    $lunesViernes = [];
    $diasSemana = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];
    
    foreach ($diasSemana as $dia) {
        if (isset($horariosPorDia[$dia]) && !empty($horariosPorDia[$dia])) {
            $horario = $horariosPorDia[$dia];
            
            try {
                $horaInicio = isset($horario['hora_inicio']) ? $horario['hora_inicio'] : '00:00:00';
                $horaFin = isset($horario['hora_fin']) ? $horario['hora_fin'] : '23:59:59';
                
                // Limpiar formato si viene con segundos
                if (is_string($horaInicio) && strlen($horaInicio) > 5) {
                    $horaInicio = substr($horaInicio, 0, 5);
                }
                if (is_string($horaFin) && strlen($horaFin) > 5) {
                    $horaFin = substr($horaFin, 0, 5);
                }
                
                $lunesViernes[] = $horaInicio . ' - ' . $horaFin;
            } catch (\Exception $e) {
                $lunesViernes[] = '00:00 - 23:59';
            }
        }
    }
    
    if (!empty($lunesViernes)) {
        $horariosUnicos = array_unique($lunesViernes);
        if (count($horariosUnicos) === 1) {
            $horarios[] = 'Lun-Vie: ' . $horariosUnicos[0];
        } else {
            $horarios[] = 'Lun-Vie: ' . implode(', ', $horariosUnicos);
        }
    }
    
    // Sábados
    if (isset($horariosPorDia['sabado']) && !empty($horariosPorDia['sabado'])) {
        $sabado = $horariosPorDia['sabado'];
        try {
            $horaInicio = isset($sabado['hora_inicio']) ? $sabado['hora_inicio'] : '00:00:00';
            $horaFin = isset($sabado['hora_fin']) ? $sabado['hora_fin'] : '23:59:59';
            
            // Limpiar formato
            if (is_string($horaInicio) && strlen($horaInicio) > 5) {
                $horaInicio = substr($horaInicio, 0, 5);
            }
            if (is_string($horaFin) && strlen($horaFin) > 5) {
                $horaFin = substr($horaFin, 0, 5);
            }
            
            $horarios[] = 'Sáb: ' . $horaInicio . ' - ' . $horaFin;
        } catch (\Exception $e) {
            $horarios[] = 'Sáb: 00:00 - 23:59';
        }
    }
    
    // Domingos
    if (isset($horariosPorDia['domingo']) && !empty($horariosPorDia['domingo'])) {
        $domingo = $horariosPorDia['domingo'];
        try {
            $horaInicio = isset($domingo['hora_inicio']) ? $domingo['hora_inicio'] : '00:00:00';
            $horaFin = isset($domingo['hora_fin']) ? $domingo['hora_fin'] : '23:59:59';
            
            // Limpiar formato
            if (is_string($horaInicio) && strlen($horaInicio) > 5) {
                $horaInicio = substr($horaInicio, 0, 5);
            }
            if (is_string($horaFin) && strlen($horaFin) > 5) {
                $horaFin = substr($horaFin, 0, 5);
            }
            
            $horarios[] = 'Dom: ' . $horaInicio . ' - ' . $horaFin;
        } catch (\Exception $e) {
            $horarios[] = 'Dom: 00:00 - 23:59';
        }
    }
    
    return empty($horarios) ? ['Sin horarios definidos'] : $horarios;
}

private function formatearTarifasParaLeyenda($tarifas)
{
    if (empty($tarifas) || !is_array($tarifas) && !is_object($tarifas)) {
        return 'Gratuito';
    }
    
    // Convertir a collection si es array
    $tarifasCollection = collect($tarifas);
    
    if ($tarifasCollection->isEmpty()) {
        return 'Gratuito';
    }
    
    $precios = $tarifasCollection->pluck('precio_por_hora')->filter()->unique()->sort()->values();
    
    if ($precios->isEmpty()) {
        return 'Gratuito';
    }
    
    if ($precios->count() === 1) {
        return '$' . number_format($precios->first(), 0);
    }
    
    return '$' . number_format($precios->min(), 0) . ' - $' . number_format($precios->max(), 0);
}

public function obtenerZonasParaMapa()
{
    try {
        $zonas = Zona::where('activa', true)
            ->with(['horarios' => function ($query) {
                $query->where('activo', true)->orderBy('dia_semana');
            }])
            ->orderBy('nombre')
            ->get()
            ->map(function ($zona) {
                return $this->formatearZonaParaMapa($zona);
            });
        
        return [
            'status' => true,
            'message' => 'Zonas para mapa obtenidas exitosamente',
            'zonas' => $zonas,
            'status_code' => 200
        ];
    } catch (\Exception $e) {
        \Log::error('Error en obtenerZonasParaMapa: ' . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());
        
        return [
            'status' => false,
            'message' => 'Error al obtener zonas para mapa',
            'error' => $e->getMessage(),
            'status_code' => 500
        ];
    }
}

private function formatearZonaParaMapa($zona)
{
    // Formatear horarios de manera legible usando la relación horarios
    $horarios = [];
    if ($zona->horarios && $zona->horarios->isNotEmpty()) {
        $horarios = $this->formatearHorariosParaLeyenda($zona->horarios->groupBy('dia_semana'));
    } else {
        $horarios = ['Sin horarios definidos'];
    }
    
    // Determinar tipo de zona
    $tipo = 'libre';
    if ($zona->es_prohibido_estacionar) {
        $tipo = 'prohibida';
    } elseif ($zona->horarios && $zona->horarios->isNotEmpty() && !$zona->es_prohibido_estacionar) {
        $tipo = 'paga';
    }

    // Formatear polígonos para Google Maps
    $poligonos = $this->formatearPoligonosParaGoogleMaps($zona->poligono_coordenadas);

    return [
        'id' => $zona->id,
        'nombre' => $zona->nombre,
        'descripcion' => $zona->descripcion,
        'tipo' => $tipo,
        'color_mapa' => $zona->color_mapa,
        'es_prohibido_estacionar' => $zona->es_prohibido_estacionar,
        'poligonos' => $poligonos,
        'horarios_formateados' => $horarios,
        'centroide' => $this->calcularCentroide($zona->poligono_coordenadas),
        'activa' => $zona->activa
    ];
}

private function formatearPoligonosParaGoogleMaps($coordenadas)
{
    if (empty($coordenadas) || !is_array($coordenadas)) {
        return [];
    }

    // Si es un polígono simple (array de coordenadas), convertir a array de polígonos
    if (isset($coordenadas[0]['lat'])) {
        $coordenadas = [$coordenadas];
    }

    $poligonos = [];
    
    foreach ($coordenadas as $index => $poligono) {
        if (is_array($poligono) && !empty($poligono)) {
            $poligonos[] = [
                'id' => $index,
                'paths' => array_map(function ($punto) {
                    return [
                        'lat' => (float) $punto['lat'],
                        'lng' => (float) $punto['lng']
                    ];
                }, $poligono)
            ];
        }
    }

    return $poligonos;
}

public function obtenerZonaPorCoordenadas($latitud, $longitud)
{
    try {
        // Buscar todas las zonas activas
        $zonas = Zona::where('activa', true)
            ->with(['horarios' => function ($query) {
                $query->where('activo', true)->orderBy('dia_semana');
            }, 'estacionamientos' => function ($query) {
                $query->where('estado', 'activo');
            }])
            ->get();

        // Verificar en cuál zona está el punto
        foreach ($zonas as $zona) {
            if ($this->puntoEnPoligono($latitud, $longitud, $zona->poligono_coordenadas)) {
                // Formatear la zona con toda la información disponible
                $zonaCompleta = $this->formatearZonaCompleta($zona, $latitud, $longitud);
                
                return [
                    'status' => true,
                    'message' => 'Zona encontrada exitosamente',
                    'zona' => $zonaCompleta,
                    'coordenadas_consultadas' => [
                        'latitud' => $latitud,
                        'longitud' => $longitud
                    ],
                    'status_code' => 200
                ];
            }
        }

        // Si no se encuentra en ninguna zona
        return [
            'status' => false,
            'message' => 'No se encontró ninguna zona para las coordenadas proporcionadas',
            'zona' => null,
            'coordenadas_consultadas' => [
                'latitud' => $latitud,
                'longitud' => $longitud
            ],
            'es_zona_libre' => true,
            'status_code' => 404
        ];

    } catch (\Exception $e) {
        \Log::error('Error en obtenerZonaPorCoordenadas: ' . $e->getMessage());
        \Log::error('Coordenadas: lat=' . $latitud . ', lng=' . $longitud);
        
        return [
            'status' => false,
            'message' => 'Error al obtener información de zona',
            'error' => $e->getMessage(),
            'zona' => null,
            'status_code' => 500
        ];
    }
}

/**
 * Formatear zona con información completa y contexto actual
 */
private function formatearZonaCompleta($zona, $latitudConsultada, $longitudConsultada)
{
    $ahora = now();
    $diaActual = strtolower($ahora->locale('es')->dayName);
    $horaActual = $ahora->format('H:i:s');

    // Información básica de la zona
    $zonaInfo = [
        'id' => $zona->id,
        'nombre' => $zona->nombre,
        'descripcion' => $zona->descripcion,
        'color_mapa' => $zona->color_mapa,
        'es_prohibido_estacionar' => $zona->es_prohibido_estacionar,
        'activa' => $zona->activa,
        'created_at' => $zona->created_at,
        'updated_at' => $zona->updated_at
    ];

    // Determinar tipo de zona
    $tipo = 'libre';
    if ($zona->es_prohibido_estacionar) {
        $tipo = 'prohibida';
    } elseif ($zona->horarios && $zona->horarios->isNotEmpty()) {
        $tipo = 'paga';
    }
    $zonaInfo['tipo'] = $tipo;

    // Información de polígonos
    $zonaInfo['poligonos'] = $this->formatearPoligonosParaGoogleMaps($zona->poligono_coordenadas);
    $zonaInfo['centroide'] = $this->calcularCentroide($zona->poligono_coordenadas);
    
    // Distancia al centroide
    if ($zonaInfo['centroide']) {
        $zonaInfo['distancia_al_centro'] = $this->calcularDistancia(
            $latitudConsultada, 
            $longitudConsultada,
            $zonaInfo['centroide']['lat'],
            $zonaInfo['centroide']['lng']
        );
    }

    // Información de horarios
    $zonaInfo['horarios'] = [
        'por_dia' => [],
        'formateados' => [],
        'esta_activa_ahora' => false,
        'horario_actual' => null,
        'proximo_cambio' => null
    ];

    if ($zona->horarios && $zona->horarios->isNotEmpty()) {
        // Horarios por día
        $horariosPorDia = $zona->horarios->groupBy('dia_semana');
        
        foreach ($horariosPorDia as $dia => $horariosDelDia) {
            $horario = $horariosDelDia->first();
            $zonaInfo['horarios']['por_dia'][$dia] = [
                'id' => $horario->id,
                'hora_inicio' => $horario->hora_inicio,
                'hora_fin' => $horario->hora_fin,
                'activo' => $horario->activo
            ];
        }

        // Horarios formateados para mostrar
        $zonaInfo['horarios']['formateados'] = $this->formatearHorariosParaLeyenda($horariosPorDia);

        // Verificar si está activa ahora
        $horarioHoy = $horariosPorDia->get($diaActual);
        if ($horarioHoy && $horarioHoy->isNotEmpty()) {
            $horario = $horarioHoy->first();
            $zonaInfo['horarios']['horario_actual'] = [
                'dia' => $diaActual,
                'hora_inicio' => $horario->hora_inicio,
                'hora_fin' => $horario->hora_fin
            ];
            
            $zonaInfo['horarios']['esta_activa_ahora'] = $horario->estaEnHorario($horaActual);
            
            // Calcular próximo cambio de estado
            $zonaInfo['horarios']['proximo_cambio'] = $this->calcularProximoCambio($horariosPorDia, $ahora);
        }
    }

    // Información de tarifas
    $zonaInfo['tarifas'] = [
        'disponibles' => [],
        'actual' => null,
        'formateada' => 'Gratuito'
    ];

    if ($tipo === 'paga') {
        try {
            $tarifasDisponibles = TarifaHorario::where('activa', true)
                ->orderBy('hora_inicio')
                ->get();

            $zonaInfo['tarifas']['disponibles'] = $tarifasDisponibles->map(function ($tarifa) {
                return [
                    'id' => $tarifa->id,
                    'nombre' => $tarifa->nombre,
                    'hora_inicio' => $tarifa->hora_inicio,
                    'hora_fin' => $tarifa->hora_fin,
                    'precio_por_hora' => (float) $tarifa->precio_por_hora,
                    'descripcion' => $tarifa->descripcion,
                    'activa' => $tarifa->activa
                ];
            });

            // Tarifa actual
            $tarifaActual = TarifaHorario::obtenerTarifaPorHora($horaActual);
            if ($tarifaActual) {
                $zonaInfo['tarifas']['actual'] = [
                    'id' => $tarifaActual->id,
                    'nombre' => $tarifaActual->nombre,
                    'precio_por_hora' => (float) $tarifaActual->precio_por_hora,
                    'descripcion' => $tarifaActual->descripcion,
                    'aplica_desde' => $tarifaActual->hora_inicio,
                    'aplica_hasta' => $tarifaActual->hora_fin
                ];
            }

            $zonaInfo['tarifas']['formateada'] = $this->formatearTarifasParaLeyenda($zonaInfo['tarifas']['disponibles']);
        } catch (\Exception $e) {
            \Log::error('Error obteniendo tarifas para zona: ' . $e->getMessage());
        }
    }

    // Información de estacionamientos activos
    $zonaInfo['estacionamientos'] = [
        'total_activos' => $zona->estacionamientos ? $zona->estacionamientos->count() : 0,
        'activos' => []
    ];

    if ($zona->estacionamientos && $zona->estacionamientos->isNotEmpty()) {
        $zonaInfo['estacionamientos']['activos'] = $zona->estacionamientos->map(function ($estacionamiento) {
            return [
                'id' => $estacionamiento->id,
                'vehiculo_id' => $estacionamiento->vehiculo_id,
                'fecha_inicio' => $estacionamiento->fecha_inicio,
                'hora_inicio' => $estacionamiento->hora_inicio,
                'estado' => $estacionamiento->estado,
                'latitud' => $estacionamiento->latitud,
                'longitud' => $estacionamiento->longitud
            ];
        });
    }

    // Estado general y recomendaciones
    $zonaInfo['estado_actual'] = [
        'puede_estacionar' => !$zona->es_prohibido_estacionar && ($zonaInfo['horarios']['esta_activa_ahora'] || $tipo === 'libre'),
        'requiere_pago' => $tipo === 'paga' && $zonaInfo['horarios']['esta_activa_ahora'],
        'mensaje' => $this->generarMensajeEstado($zona, $zonaInfo),
        'recomendacion' => $this->generarRecomendacion($zona, $zonaInfo)
    ];

    return $zonaInfo;
}

/**
 * Calcular distancia entre dos puntos en kilómetros
 */
private function calcularDistancia($lat1, $lng1, $lat2, $lng2)
{
    $radioTierra = 6371; // Radio de la Tierra en kilómetros
    
    $deltaLat = deg2rad($lat2 - $lat1);
    $deltaLng = deg2rad($lng2 - $lng1);
    
    $a = sin($deltaLat/2) * sin($deltaLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($deltaLng/2) * sin($deltaLng/2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    
    return round($radioTierra * $c * 1000, 2); // Retornar en metros
}

/**
 * Calcular próximo cambio de estado de la zona
 */
private function calcularProximoCambio($horariosPorDia, $ahora)
{
    $diaActual = strtolower($ahora->locale('es')->dayName);
    $horaActual = $ahora->format('H:i:s');
    
    $horarioHoy = $horariosPorDia->get($diaActual);
    
    if ($horarioHoy && $horarioHoy->isNotEmpty()) {
        $horario = $horarioHoy->first();
        
        if ($horaActual < $horario->hora_inicio) {
            return [
                'tipo' => 'activacion',
                'hora' => $horario->hora_inicio,
                'mensaje' => 'La zona se activará a las ' . substr($horario->hora_inicio, 0, 5)
            ];
        } elseif ($horaActual >= $horario->hora_inicio && $horaActual < $horario->hora_fin) {
            return [
                'tipo' => 'desactivacion',
                'hora' => $horario->hora_fin,
                'mensaje' => 'La zona se desactivará a las ' . substr($horario->hora_fin, 0, 5)
            ];
        }
    }
    
    return [
        'tipo' => 'ninguno',
        'hora' => null,
        'mensaje' => 'No hay cambios programados para hoy'
    ];
}

/**
 * Generar mensaje descriptivo del estado actual
 */
private function generarMensajeEstado($zona, $zonaInfo)
{
    if ($zona->es_prohibido_estacionar) {
        return 'Prohibido estacionar en esta zona';
    }
    
    if ($zonaInfo['tipo'] === 'libre') {
        return 'Zona libre - Estacionamiento gratuito sin restricciones';
    }
    
    if ($zonaInfo['horarios']['esta_activa_ahora']) {
        $tarifa = $zonaInfo['tarifas']['actual'];
        $precio = $tarifa ? '$' . number_format($tarifa['precio_por_hora'], 0) . '/hora' : 'Consultar tarifa';
        return 'Zona activa - Estacionamiento pago (' . $precio . ')';
    }
    
    return 'Zona inactiva - Estacionamiento gratuito temporalmente';
}

/**
 * Generar recomendación para el usuario
 */
private function generarRecomendacion($zona, $zonaInfo)
{
    if ($zona->es_prohibido_estacionar) {
        return 'Busque otra zona para estacionar';
    }
    
    if (!$zonaInfo['estado_actual']['puede_estacionar']) {
        return 'No es posible estacionar en este momento';
    }
    
    if ($zonaInfo['estado_actual']['requiere_pago']) {
        $proximoCambio = $zonaInfo['horarios']['proximo_cambio'];
        $mensaje = 'Recuerde activar el estacionamiento pago';
        if ($proximoCambio && $proximoCambio['tipo'] === 'desactivacion') {
            $mensaje .= '. ' . $proximoCambio['mensaje'];
        }
        return $mensaje;
    }
    
    return 'Puede estacionar libremente';
}
}