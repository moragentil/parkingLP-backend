<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Implementation\VehiculoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VehiculoController extends Controller
{
    protected $vehiculoService;

    public function __construct(VehiculoService $vehiculoService)
    {
        $this->vehiculoService = $vehiculoService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $resultado = $this->vehiculoService->obtenerVehiculosUsuario($request->user()->id);
        return response()->json($resultado, $resultado['status_code']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patente' => 'required|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $resultado = $this->vehiculoService->crearVehiculo($request->user()->id, $request->all());
        return response()->json($resultado, $resultado['status_code']);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        $resultado = $this->vehiculoService->obtenerVehiculo($request->user()->id, $id);
        return response()->json($resultado, $resultado['status_code']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'patente' => 'sometimes|required|string|max:10',
            'ubicacion_actual_lat' => 'nullable|numeric|between:-90,90',
            'ubicacion_actual_lng' => 'nullable|numeric|between:-180,180',
            'fecha_estacionamiento' => 'nullable|date',
            'hora_estacionamiento' => 'nullable|date_format:H:i:s',
            'activo' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $resultado = $this->vehiculoService->actualizarVehiculo($request->user()->id, $id, $request->all());
        return response()->json($resultado, $resultado['status_code']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        $resultado = $this->vehiculoService->eliminarVehiculo($request->user()->id, $id);
        return response()->json($resultado, $resultado['status_code']);
    }

    /**
     * Obtener el vehículo activo (con estacionamiento activo)
     */
    public function vehiculoActivo(Request $request)
    {
        $resultado = $this->vehiculoService->obtenerVehiculoActivo($request->user()->id);
        return response()->json($resultado, $resultado['status_code']);
    }

    /**
     * Actualizar la ubicación del vehículo
     */
    public function actualizarUbicacion(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'latitud' => 'required|numeric|between:-90,90',
            'longitud' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $resultado = $this->vehiculoService->actualizarUbicacion(
            $request->user()->id, 
            $id, 
            $request->latitud, 
            $request->longitud
        );
        return response()->json($resultado, $resultado['status_code']);
    }
}
