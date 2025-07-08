<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Implementation\PlanoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PlanoController extends Controller
{
    protected $planoService;

    public function __construct(PlanoService $planoService)
    {
        $this->planoService = $planoService;
    }

    public function index()
    {
        $resultado = $this->planoService->obtenerTodosLosPlanos();
        return response()->json($resultado, $resultado['status_code']);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'version' => 'nullable|string|max:50',
            'fecha_actualizacion' => 'nullable|date',
            'zonas' => 'nullable|array',
            'zonas.*' => 'integer|exists:zonas,id',
            'descripcion' => 'nullable|string|max:500',
            'activo' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $resultado = $this->planoService->crearPlano($request->all());
        return response()->json($resultado, $resultado['status_code']);
    }

    public function show($id)
    {
        $resultado = $this->planoService->obtenerPlano($id);
        return response()->json($resultado, $resultado['status_code']);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'version' => 'sometimes|required|string|max:50',
            'fecha_actualizacion' => 'nullable|date',
            'zonas' => 'nullable|array',
            'zonas.*' => 'integer|exists:zonas,id',
            'descripcion' => 'nullable|string|max:500',
            'activo' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $resultado = $this->planoService->actualizarPlano($id, $request->all());
        return response()->json($resultado, $resultado['status_code']);
    }

    public function destroy($id)
    {
        // Por seguridad, no permitir eliminar planos, solo desactivar
        return response()->json([
            'status' => false,
            'message' => 'No se permite eliminar planos. Use desactivar en su lugar.',
            'status_code' => 405
        ], 405);
    }

    public function planoVigente()
    {
        $resultado = $this->planoService->obtenerPlanoVigente();
        return response()->json($resultado, $resultado['status_code']);
    }

    public function activar($id)
    {
        $resultado = $this->planoService->activarPlano($id);
        return response()->json($resultado, $resultado['status_code']);
    }

    public function planosPorFecha(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $resultado = $this->planoService->obtenerPlanosPorFecha(
            $request->fecha_inicio,
            $request->fecha_fin
        );
        return response()->json($resultado, $resultado['status_code']);
    }

    public function historial()
    {
        $resultado = $this->planoService->obtenerHistorialPlanos();
        return response()->json($resultado, $resultado['status_code']);
    }

    public function duplicar(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'version' => 'nullable|string|max:50',
            'descripcion' => 'nullable|string|max:500',
            'duplicar_zonas' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $resultado = $this->planoService->duplicarPlano($id, $request->all());
        return response()->json($resultado, $resultado['status_code']);
    }

    public function agregarZona(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'zona_id' => 'required|integer|exists:zonas,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $plano = \App\Models\Plano::findOrFail($id);
            $zonas = $plano->zonas ?? [];
            
            if (!in_array($request->zona_id, $zonas)) {
                $zonas[] = $request->zona_id;
                $plano->update(['zonas' => $zonas]);
            }
            
            return response()->json([
                'status' => true,
                'message' => 'Zona agregada al plano exitosamente',
                'plano' => $plano,
                'status_code' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error al agregar zona',
                'error' => $e->getMessage(),
                'status_code' => 500
            ], 500);
        }
    }

    public function removerZona(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'zona_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $plano = \App\Models\Plano::findOrFail($id);
            $zonas = $plano->zonas ?? [];
            
            $zonas = array_values(array_filter($zonas, function($zona) use ($request) {
                return $zona != $request->zona_id;
            }));
            
            $plano->update(['zonas' => $zonas]);
            
            return response()->json([
                'status' => true,
                'message' => 'Zona removida del plano exitosamente',
                'plano' => $plano,
                'status_code' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error al remover zona',
                'error' => $e->getMessage(),
                'status_code' => 500
            ], 500);
        }
    }
}