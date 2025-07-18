<?php

namespace App\Services\Interface;

interface ZonaServiceInterface
{
    public function obtenerTodasLasZonas();
    public function obtenerZonasActivas();
    public function obtenerZona($zonaId);
    public function crearZona(array $datos);
    public function actualizarZona($zonaId, array $datos);
    public function activarDesactivarZona($zonaId, $activa);
    public function obtenerZonasPorTipo($tipo);
    public function verificarPuntoEnZona($latitud, $longitud, $zonaId);
    public function obtenerZonasCercanas($latitud, $longitud, $radio);
    public function obtenerTarifasHorarias(); // Nuevo método
}