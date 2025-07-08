<?php

namespace App\Services\Interface;

interface AlarmaServiceInterface
{
    public function obtenerAlarmasUsuario($usuarioId);
    public function crearAlarma($estacionamientoId, array $datos);
    public function obtenerAlarma($alarmaId);
    public function actualizarAlarma($alarmaId, array $datos);
    public function eliminarAlarma($alarmaId);
    public function marcarComoEnviada($alarmaId);
    public function obtenerAlarmasActivas();
    public function obtenerAlarmasPendientes();
    public function desactivarAlarma($alarmaId);
}