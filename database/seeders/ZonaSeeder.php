<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Zona;
use App\Models\ZonaHorario;
use Illuminate\Support\Facades\DB;

class ZonaSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Zona::truncate();
        ZonaHorario::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $zonasData = [
            [
                'zona' => [
                    'nombre' => 'Zona Rosa',
                    'descripcion' => 'Zona de estacionamiento tarifado rosa',
                    'color_mapa' => '#f92fa5',
                    'poligono_coordenadas' => [
                        ['lat' => -34.9106539271485, 'lng' => -57.95526603707086],
                        ['lat' => -34.91812366482644, 'lng' => -57.96349047471355],
                        ['lat' => -34.92178673333635, 'lng' => -57.95860908690585],
                        ['lat' => -34.92154644440495, 'lng' => -57.956883298847615],
                        ['lat' => -34.919544009275235, 'lng' => -57.95473420428323],
                        ['lat' => -34.9106539271485, 'lng' => -57.95526603707086]
                    ],
                    'es_prohibido_estacionar' => false,
                    'activa' => true
                ],
                'horarios' => [
                    ['dia_semana' => 'lunes', 'hora_inicio' => '07:00:00', 'hora_fin' => '14:00:00'],
                    ['dia_semana' => 'martes', 'hora_inicio' => '07:00:00', 'hora_fin' => '14:00:00'],
                    ['dia_semana' => 'miercoles', 'hora_inicio' => '07:00:00', 'hora_fin' => '14:00:00'],
                    ['dia_semana' => 'jueves', 'hora_inicio' => '07:00:00', 'hora_fin' => '14:00:00'],
                    ['dia_semana' => 'viernes', 'hora_inicio' => '07:00:00', 'hora_fin' => '14:00:00']
                ]
            ],
            [
                'zona' => [
                    'nombre' => 'Zona Azul',
                    'descripcion' => 'Zona de estacionamiento tarifado azul',
                    'color_mapa' => '#364cec',
                    'poligono_coordenadas' => [
                        ['lat' => -34.92232676738777, 'lng' => -57.95309515759354],
                        ['lat' => -34.92963818736148, 'lng' => -57.94318333950386],
                        ['lat' => -34.92875474576077, 'lng' => -57.94224002451598],
                        ['lat' => -34.92728137584504, 'lng' => -57.94224407037021],
                        ['lat' => -34.92151870887917, 'lng' => -57.950179251471454],
                        ['lat' => -34.92148543754821, 'lng' => -57.95224797287344],
                        ['lat' => -34.92232676738777, 'lng' => -57.95309515759354]
                    ],
                    'es_prohibido_estacionar' => false,
                    'activa' => true
                ],
                'horarios' => [
                    ['dia_semana' => 'lunes', 'hora_inicio' => '09:00:00', 'hora_fin' => '20:00:00'],
                    ['dia_semana' => 'martes', 'hora_inicio' => '09:00:00', 'hora_fin' => '20:00:00'],
                    ['dia_semana' => 'miercoles', 'hora_inicio' => '09:00:00', 'hora_fin' => '20:00:00'],
                    ['dia_semana' => 'jueves', 'hora_inicio' => '09:00:00', 'hora_fin' => '20:00:00'],
                    ['dia_semana' => 'viernes', 'hora_inicio' => '09:00:00', 'hora_fin' => '20:00:00'],
                    ['dia_semana' => 'sabado', 'hora_inicio' => '09:00:00', 'hora_fin' => '20:00:00']
                ]
            ],
            [
                'zona' => [
                    'nombre' => 'Zona Verde 1',
                    'descripcion' => 'Zona libre de estacionamiento',
                    'color_mapa' => '#1fb10b',
                    'poligono_coordenadas' => [
                        ['lat' => -34.91065503503199, 'lng' => -57.95520012614702],
                        ['lat' => -34.914746293770484, 'lng' => -57.949569373914215],
                        ['lat' => -34.91391843647664, 'lng' => -57.948600739882494],
                        ['lat' => -34.91444512881721, 'lng' => -57.947902951915125],
                        ['lat' => -34.912585418089634, 'lng' => -57.94584942303739],
                        ['lat' => -34.91045297138117, 'lng' => -57.948648911675264],
                        ['lat' => -34.90671966232512, 'lng' => -57.94888146689263],
                        ['lat' => -34.9069639708534, 'lng' => -57.949144851096264],
                        ['lat' => -34.90998160527831, 'lng' => -57.94899256328479],
                        ['lat' => -34.91065503503199, 'lng' => -57.95520012614702]
                    ],
                    'es_prohibido_estacionar' => false,
                    'activa' => true
                ],
                'horarios' => [
                    // Lunes a viernes 7:00 a 20:00
                    ['dia_semana' => 'lunes', 'hora_inicio' => '07:00:00', 'hora_fin' => '20:00:00'],
                    ['dia_semana' => 'martes', 'hora_inicio' => '07:00:00', 'hora_fin' => '20:00:00'],
                    ['dia_semana' => 'miercoles', 'hora_inicio' => '07:00:00', 'hora_fin' => '20:00:00'],
                    ['dia_semana' => 'jueves', 'hora_inicio' => '07:00:00', 'hora_fin' => '20:00:00'],
                    ['dia_semana' => 'viernes', 'hora_inicio' => '07:00:00', 'hora_fin' => '20:00:00'],
                    // Sábados 9:00 a 20:00 (horario diferente)
                    ['dia_semana' => 'sabado', 'hora_inicio' => '09:00:00', 'hora_fin' => '20:00:00']
                ]
            ],
            [
                'zona' => [
                    'nombre' => 'Zona Prohibida 1',
                    'descripcion' => 'Zona de prohibición de estacionamiento',
                    'color_mapa' => '#0d0d0d',
                    'poligono_coordenadas' => [
                        ['lat' => -34.91124323438609, 'lng' => -57.954769311440245],
                        ['lat' => -34.91697591835763, 'lng' => -57.94721790610541],
                        ['lat' => -34.916629959173925, 'lng' => -57.94691459465379],
                        ['lat' => -34.91105330646013, 'lng' => -57.954604892600926],
                        ['lat' => -34.91124323438609, 'lng' => -57.954769311440245]
                    ],
                    'es_prohibido_estacionar' => true,
                    'activa' => true
                ],
                'horarios' => [
                    ['dia_semana' => 'lunes', 'hora_inicio' => '00:00:00', 'hora_fin' => '23:59:59'],
                    ['dia_semana' => 'martes', 'hora_inicio' => '00:00:00', 'hora_fin' => '23:59:59'],
                    ['dia_semana' => 'miercoles', 'hora_inicio' => '00:00:00', 'hora_fin' => '23:59:59'],
                    ['dia_semana' => 'jueves', 'hora_inicio' => '00:00:00', 'hora_fin' => '23:59:59'],
                    ['dia_semana' => 'viernes', 'hora_inicio' => '00:00:00', 'hora_fin' => '23:59:59'],
                    ['dia_semana' => 'sabado', 'hora_inicio' => '00:00:00', 'hora_fin' => '23:59:59'],
                    ['dia_semana' => 'domingo', 'hora_inicio' => '00:00:00', 'hora_fin' => '23:59:59']
                ]
            ]
        ];

        foreach ($zonasData as $zonaDatos) {
            $zona = Zona::create($zonaDatos['zona']);
            
            foreach ($zonaDatos['horarios'] as $horario) {
                ZonaHorario::create([
                    'zona_id' => $zona->id,
                    'dia_semana' => $horario['dia_semana'],
                    'hora_inicio' => $horario['hora_inicio'],
                    'hora_fin' => $horario['hora_fin'],
                    'activo' => true
                ]);
            }
        }

        $this->command->info('Se han creado las zonas con sus horarios específicos exitosamente.');
    }
}
