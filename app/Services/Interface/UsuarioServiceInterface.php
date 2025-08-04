<?php

namespace App\Services\Interface;

interface UsuarioServiceInterface
{
    public function obtenerTodosLosUsuarios();
    public function obtenerUsuario($usuarioId);
    public function crearUsuario(array $datos);
    public function actualizarUsuario($usuarioId, array $datos);
    public function eliminarUsuario($usuarioId);
    public function cambiarPassword($usuarioId, $passwordActual, $passwordNueva);
    public function actualizarPerfil($usuarioId, array $datos);
    public function buscarUsuarios($criterio);
}