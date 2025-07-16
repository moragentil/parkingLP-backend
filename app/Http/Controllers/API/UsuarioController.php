<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Implementation\UsuarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UsuarioController extends Controller
{
    protected $usuarioService;

    public function __construct(UsuarioService $usuarioService)
    {
        $this->usuarioService = $usuarioService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $resultado = $this->usuarioService->obtenerTodosLosUsuarios();
        return response()->json($resultado, $resultado['status_code']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:usuarios',
            'telefono' => 'nullable|string|max:20',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $resultado = $this->usuarioService->crearUsuario($request->all());
        return response()->json($resultado, $resultado['status_code']);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        // Si el ID es 'me', obtener el usuario autenticado
        if ($id === 'me') {
            return response()->json([
                'status' => true,
                'usuario' => $request->user(),
                'status_code' => 200
            ]);
        }

        $resultado = $this->usuarioService->obtenerUsuario($id);
        return response()->json($resultado, $resultado['status_code']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255',
            'telefono' => 'nullable|string|max:20',
            'password' => 'sometimes|required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $resultado = $this->usuarioService->actualizarUsuario($id, $request->all());
        return response()->json($resultado, $resultado['status_code']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $resultado = $this->usuarioService->eliminarUsuario($id);
        return response()->json($resultado, $resultado['status_code']);
    }

    /**
     * Cambiar contraseña del usuario
     */
    public function cambiarPassword(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'password_actual' => 'required|string',
            'password_nueva' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $resultado = $this->usuarioService->cambiarPassword(
            $id,
            $request->password_actual,
            $request->password_nueva
        );
        return response()->json($resultado, $resultado['status_code']);
    }

    /**
     * Buscar usuarios
     */
    public function buscar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'criterio' => 'required|string|min:3',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $resultado = $this->usuarioService->buscarUsuarios($request->criterio);
        return response()->json($resultado, $resultado['status_code']);
    }
}
