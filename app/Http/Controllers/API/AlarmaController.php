<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Implementation\AlarmaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AlarmaController extends Controller
{
    protected $alarmaService;

    public function __construct(AlarmaService $alarmaService)
    {
        $this->alarmaService = $alarmaService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $resultado = $this->alarmaService->obtenerAlarmasUsuario($request->user()->id);
        return response()->json($resultado, $resultado['status_code']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'estacionamiento_id' => 'required|integer|exists:estacionamientos,id',
            'hora_alarma' => 'required|date|after:now',
            'mensaje' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $resultado = $this->alarmaService->crearAlarma($request->estacionamiento_id, $request->all());
        return response()->json($resultado, $resultado['status_code']);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $resultado = $this->alarmaService->obtenerAlarma($id);
        return response()->json($resultado, $resultado['status_code']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'hora_alarma' => 'sometimes|required|date|after:now',
            'mensaje' => 'nullable|string|max:500',
            'activa' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $resultado = $this->alarmaService->actualizarAlarma($id, $request->all());
        return response()->json($resultado, $resultado['status_code']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $resultado = $this->alarmaService->eliminarAlarma($id);
        return response()->json($resultado, $resultado['status_code']);
    }

    public function marcarEnviada($id)
    {
        $resultado = $this->alarmaService->marcarComoEnviada($id);
        return response()->json($resultado, $resultado['status_code']);
    }

    public function alarmasActivas()
    {
        $resultado = $this->alarmaService->obtenerAlarmasActivas();
        return response()->json($resultado, $resultado['status_code']);
    }

    public function alarmasPendientes()
    {
        $resultado = $this->alarmaService->obtenerAlarmasPendientes();
        return response()->json($resultado, $resultado['status_code']);
    }

    public function desactivar($id)
    {
        $resultado = $this->alarmaService->desactivarAlarma($id);
        return response()->json($resultado, $resultado['status_code']);
    }
}
