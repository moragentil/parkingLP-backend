<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Implementation\EstacionamientoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EstacionamientoController extends Controller
{
    protected $estacionamientoService;

    public function __construct(EstacionamientoService $estacionamientoService)
    {
        $this->estacionamientoService = $estacionamientoService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $resultado = $this->estacionamientoService->obtenerEstacionamientosUsuario($request->user()->id);
        return response()->json($resultado, $resultado['status_code']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vehiculo_id' => 'required|integer|exists:vehiculos,id',
            'latitud' => 'required|numeric|between:-90,90',
            'longitud' => 'required|numeric|between:-180,180',
            'direccion' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $resultado = $this->estacionamientoService->iniciarEstacionamiento($request->user()->id, $request->all());
        return response()->json($resultado, $resultado['status_code']);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        $resultado = $this->estacionamientoService->obtenerEstacionamiento($request->user()->id, $id);
        return response()->json($resultado, $resultado['status_code']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'estado' => 'required|in:activo,finalizado,vencido,cancelado',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $resultado = $this->estacionamientoService->actualizarEstado($id, $request->estado);
        return response()->json($resultado, $resultado['status_code']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        // Por seguridad, no permitir eliminar estacionamientos
        return response()->json([
            'status' => false,
            'message' => 'No se permite eliminar estacionamientos',
            'status_code' => 405
        ], 405);
    }

    public function finalizar(Request $request, $id)
    {
        $resultado = $this->estacionamientoService->finalizarEstacionamiento($request->user()->id, $id);
        return response()->json($resultado, $resultado['status_code']);
    }

    public function verificarZona(Request $request)
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

        $zona = $this->estacionamientoService->verificarZona($request->latitud, $request->longitud);
        
        return response()->json([
            'status' => true,
            'message' => $zona ? 'Zona encontrada' : 'Zona libre',
            'zona' => $zona,
            'es_zona_paga' => $zona ? !$zona->es_prohibido_estacionar : false,
            'status_code' => 200
        ]);
    }

    public function estacionamientoActivo(Request $request)
    {
        $resultado = $this->estacionamientoService->obtenerEstacionamientoActivo($request->user()->id);
        return response()->json($resultado, $resultado['status_code']);
    }
}
