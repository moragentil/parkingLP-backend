<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        Usuario::create([
            'nombre' => 'Usuario Prueba',
            'email' => 'prueba@parkinglp.com',
            'password' => Hash::make('password123'),
            'telefono' => '2211234567',
            'activo' => true
        ]);
    }
}