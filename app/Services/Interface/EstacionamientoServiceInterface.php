<?php

namespace App\Services\Interface;

interface EstacionamientoServiceInterface
{
    public function obtenerEstacionamientosUsuario($usuarioId);
    public function iniciarEstacionamiento($usuarioId, array $datos);
    public function obtenerEstacionamiento($usuarioId, $estacionamientoId);
    public function finalizarEstacionamiento($usuarioId, $estacionamientoId);
    public function obtenerEstacionamientoActivo($usuarioId);
    public function verificarZona($latitud, $longitud);
    public function calcularCosto($estacionamientoId);
    public function actualizarEstado($estacionamientoId, $estado);
}