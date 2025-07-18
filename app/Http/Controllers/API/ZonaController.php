<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Implementation\ZonaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ZonaController extends Controller
{
    protected $zonaService;

    public function __construct(ZonaService $zonaService)
    {
        $this->zonaService = $zonaService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $resultado = $this->zonaService->obtenerTodasLasZonas();
        return response()->json($resultado, $resultado['status_code']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100|unique:zonas',
            'descripcion' => 'nullable|string|max:255',
            'color_mapa' => 'nullable|string|max:7',
            'poligono_coordenadas' => 'nullable|array',
            'poligono_coordenadas.*.lat' => 'required_with:poligono_coordenadas|numeric|between:-90,90',
            'poligono_coordenadas.*.lng' => 'required_with:poligono_coordenadas|numeric|between:-180,180',
            'hora_inicio' => 'nullable|date_format:H:i:s',
            'hora_fin' => 'nullable|date_format:H:i:s',
            'dias_habilitados' => 'nullable|array',
            'dias_habilitados.*' => 'string|in:lunes,martes,miercoles,jueves,viernes,sabado,domingo',
            'tarifa_por_hora' => 'nullable|numeric|min:0',
            'es_prohibido_estacionar' => 'boolean',
            'activa' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $resultado = $this->zonaService->crearZona($request->all());
        return response()->json($resultado, $resultado['status_code']);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $resultado = $this->zonaService->obtenerZona($id);
        return response()->json($resultado, $resultado['status_code']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'color_mapa' => 'nullable|string|max:7',
            'poligono_coordenadas' => 'nullable|array',
            'poligono_coordenadas.*.lat' => 'required_with:poligono_coordenadas|numeric|between:-90,90',
            'poligono_coordenadas.*.lng' => 'required_with:poligono_coordenadas|numeric|between:-180,180',
            'hora_inicio' => 'nullable|date_format:H:i:s',
            'hora_fin' => 'nullable|date_format:H:i:s',
            'dias_habilitados' => 'nullable|array',
            'dias_habilitados.*' => 'string|in:lunes,martes,miercoles,jueves,viernes,sabado,domingo',
            'tarifa_por_hora' => 'nullable|numeric|min:0',
            'es_prohibido_estacionar' => 'boolean',
            'activa' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $resultado = $this->zonaService->actualizarZona($id, $request->all());
        return response()->json($resultado, $resultado['status_code']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        // En lugar de eliminar, desactivar la zona
        $resultado = $this->zonaService->activarDesactivarZona($id, false);
        return response()->json($resultado, $resultado['status_code']);
    }

    public function zonasActivas()
    {
        $resultado = $this->zonaService->obtenerZonasActivas();
        return response()->json($resultado, $resultado['status_code']);
    }

    public function zonasPorTipo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tipo' => 'required|in:pago,libre,prohibido'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $resultado = $this->zonaService->obtenerZonasPorTipo($request->tipo);
        return response()->json($resultado, $resultado['status_code']);
    }

    public function verificarPunto(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'latitud' => 'required|numeric|between:-90,90',
            'longitud' => 'required|numeric|between:-180,180'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $resultado = $this->zonaService->verificarPuntoEnZona(
            $request->latitud,
            $request->longitud,
            $id
        );
        return response()->json($resultado, $resultado['status_code']);
    }

    public function zonasCercanas(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitud' => 'required|numeric|between:-90,90',
            'longitud' => 'required|numeric|between:-180,180',
            'radio' => 'nullable|numeric|min:0|max:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $radio = $request->radio ?? 0.01; // 0.01 grados ~ 1km aprox

        $resultado = $this->zonaService->obtenerZonasCercanas(
            $request->latitud,
            $request->longitud,
            $radio
        );
        return response()->json($resultado, $resultado['status_code']);
    }

    public function activarZona($id)
    {
        $resultado = $this->zonaService->activarDesactivarZona($id, true);
        return response()->json($resultado, $resultado['status_code']);
    }

    public function desactivarZona($id)
    {
        $resultado = $this->zonaService->activarDesactivarZona($id, false);
        return response()->json($resultado, $resultado['status_code']);
    }

    public function tarifasHorarias()
    {
        $resultado = $this->zonaService->obtenerTarifasHorarias();
        return response()->json($resultado, $resultado['status_code']);
    }

    public function obtenerLeyendaZonas()
    {
        $resultado = $this->zonaService->obtenerLeyendaZonas();
        return response()->json($resultado, $resultado['status_code']);
    }
}
