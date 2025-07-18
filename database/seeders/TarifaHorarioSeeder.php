<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TarifaHorario;

class TarifaHorarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TarifaHorario::truncate();

        $tarifas = [
            [
                'nombre' => 'Tarifa Matutina',
                'hora_inicio' => '07:00:00',
                'hora_fin' => '10:00:00',
                'precio_por_hora' => 300.00,
                'descripcion' => 'Tarifa reducida de mañana (7:00 a 10:00)',
                'activa' => true
            ],
            [
                'nombre' => 'Tarifa Central',
                'hora_inicio' => '10:00:00',
                'hora_fin' => '14:00:00',
                'precio_por_hora' => 500.00,
                'descripcion' => 'Tarifa alta del mediodía (10:00 a 14:00)',
                'activa' => true
            ],
            [
                'nombre' => 'Tarifa Vespertina',
                'hora_inicio' => '14:00:00',
                'hora_fin' => '20:00:00',
                'precio_por_hora' => 300.00,
                'descripcion' => 'Tarifa reducida de tarde (14:00 a 20:00)',
                'activa' => true
            ]
        ];

        foreach ($tarifas as $tarifaData) {
            TarifaHorario::create($tarifaData);
        }

        $this->command->info('Se han creado ' . count($tarifas) . ' tarifas horarias exitosamente.');
    }
}
