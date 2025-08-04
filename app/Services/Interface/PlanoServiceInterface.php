<?php

namespace App\Services\Interface;

interface PlanoServiceInterface
{
    public function obtenerTodosLosPlanos();
    public function obtenerPlanoVigente();
    public function obtenerPlano($planoId);
    public function crearPlano(array $datos);
    public function actualizarPlano($planoId, array $datos);
    public function activarPlano($planoId);
    public function obtenerPlanosPorFecha($fechaInicio, $fechaFin);
    public function obtenerHistorialPlanos();
    public function duplicarPlano($planoId, array $nuevaData);
}