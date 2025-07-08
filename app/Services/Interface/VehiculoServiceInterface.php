<?php

namespace App\Services\Interface;

use App\Models\Vehiculo;

interface VehiculoServiceInterface
{
    public function obtenerVehiculosUsuario($usuarioId);
    public function crearVehiculo($usuarioId, array $datos);
    public function obtenerVehiculo($usuarioId, $vehiculoId);
    public function actualizarVehiculo($usuarioId, $vehiculoId, array $datos);
    public function eliminarVehiculo($usuarioId, $vehiculoId);
    public function obtenerVehiculoActivo($usuarioId);
}