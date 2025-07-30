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
            // ZONA ROSA - Lunes a viernes de 7 a 14 Hs.
            [
                'zona' => [
                    'nombre' => 'Zona Rosa',
                    'descripcion' => 'Zona de estacionamiento tarifado rosa - Lunes a viernes de 7 a 14 Hs.',
                    'color_mapa' => '#f92fa5',
                    'poligono_coordenadas' => [
                        // Polígono 1 (único polígono para zona rosa)
                        [
                            ['lat' => -34.9106539271485, 'lng' => -57.95526603707086],
                            ['lat' => -34.91812366482644, 'lng' => -57.96349047471355],
                            ['lat' => -34.92178673333635, 'lng' => -57.95860908690585],
                            ['lat' => -34.92154644440495, 'lng' => -57.956883298847615],
                            ['lat' => -34.919544009275235, 'lng' => -57.95473420428323],
                            ['lat' => -34.9106539271485, 'lng' => -57.95526603707086]
                        ]
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

            // ZONA AZUL - Lunes a sábados de 9 a 20 Hs.
            [
                'zona' => [
                    'nombre' => 'Zona Azul',
                    'descripcion' => 'Zona de estacionamiento tarifado azul - Lunes a sábados de 9 a 20 Hs.',
                    'color_mapa' => '#364cec',
                    'poligono_coordenadas' => [
                        // Polígono 1 (único polígono para zona azul)
                        [
                            ['lat' => -34.92232676738777, 'lng' => -57.95309515759354],
                            ['lat' => -34.92963818736148, 'lng' => -57.94318333950386],
                            ['lat' => -34.92875474576077, 'lng' => -57.94224002451598],
                            ['lat' => -34.92728137584504, 'lng' => -57.94224407037021],
                            ['lat' => -34.92151870887917, 'lng' => -57.950179251471454],
                            ['lat' => -34.92148543754821, 'lng' => -57.95224797287344],
                            ['lat' => -34.92232676738777, 'lng' => -57.95309515759354]
                        ]
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

            // ZONA VERDE - Lunes a viernes de 7 a 20 Hs. Sábados de 9 a 20 Hs. (3 polígonos)
            [
                'zona' => [
                    'nombre' => 'Zona Verde',
                    'descripcion' => 'Zona libre de estacionamiento - Lunes a viernes de 7 a 20 Hs. Sábados de 9 a 20 Hs.',
                    'color_mapa' => '#1fb10b',
                    'poligono_coordenadas' => [
                        // Polígono 1
                        [
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
                        // Polígono 2
                        [
                            ['lat' => -34.91069881405024, 'lng' => -57.95519485348558],
                            ['lat' => -34.91964723834575, 'lng' => -57.95467152942979],
                            ['lat' => -34.920076565032524, 'lng' => -57.95416598339952],
                            ['lat' => -34.916379549414955, 'lng' => -57.95014053431963],
                            ['lat' => -34.914025494785335, 'lng' => -57.95331046725954],
                            ['lat' => -34.91390813775711, 'lng' => -57.95319060276066],
                            ['lat' => -34.91628690503395, 'lng' => -57.950020519644454],
                            ['lat' => -34.915480135512496, 'lng' => -57.949107986745844],
                            ['lat' => -34.911331867313905, 'lng' => -57.954667406474314],
                            ['lat' => -34.91069881405024, 'lng' => -57.95519485348558]
                        ],
                        // Polígono 3
                        [
                            ['lat' => -34.91676263219906, 'lng' => -57.946949160644806],
                            ['lat' => -34.916952752055636, 'lng' => -57.94714482613951],
                            ['lat' => -34.91871719502433, 'lng' => -57.94475462484748],
                            ['lat' => -34.918462642758804, 'lng' => -57.94455868715566],
                            ['lat' => -34.91676263219906, 'lng' => -57.946949160644806]
                        ]
                    ],
                    'es_prohibido_estacionar' => false,
                    'activa' => true
                ],
                'horarios' => [
                    ['dia_semana' => 'lunes', 'hora_inicio' => '07:00:00', 'hora_fin' => '20:00:00'],
                    ['dia_semana' => 'martes', 'hora_inicio' => '07:00:00', 'hora_fin' => '20:00:00'],
                    ['dia_semana' => 'miercoles', 'hora_inicio' => '07:00:00', 'hora_fin' => '20:00:00'],
                    ['dia_semana' => 'jueves', 'hora_inicio' => '07:00:00', 'hora_fin' => '20:00:00'],
                    ['dia_semana' => 'viernes', 'hora_inicio' => '07:00:00', 'hora_fin' => '20:00:00'],
                    ['dia_semana' => 'sabado', 'hora_inicio' => '09:00:00', 'hora_fin' => '20:00:00']
                ]
            ],

            // ZONA AMARILLA - Lunes a viernes de 7 a 20 Hs. (2 polígonos)
            [
                'zona' => [
                    'nombre' => 'Zona Amarilla',
                    'descripcion' => 'Zona de estacionamiento tarifado amarilla - Lunes a viernes de 7 a 20 Hs.',
                    'color_mapa' => '#e7e00d',
                    'poligono_coordenadas' => [
                        // Polígono 1
                        [
                            ['lat' => -34.91395782827249, 'lng' => -57.9485855804578],
                            ['lat' => -34.91471432534428, 'lng' => -57.94951147199694],
                            ['lat' => -34.91669716933363, 'lng' => -57.9470654246071],
                            ['lat' => -34.91392511292646, 'lng' => -57.943979939011996],
                            ['lat' => -34.91260199645613, 'lng' => -57.94581731637287],
                            ['lat' => -34.91447606274633, 'lng' => -57.94790088266184],
                            ['lat' => -34.91395782827249, 'lng' => -57.9485855804578]
                        ],
                        // Polígono 2
                        [
                            ['lat' => -34.91558341510686, 'lng' => -57.949162527718784],
                            ['lat' => -34.92006720366313, 'lng' => -57.95406316418368],
                            ['lat' => -34.91971027392631, 'lng' => -57.954762075620565],
                            ['lat' => -34.92144269862656, 'lng' => -57.95660725993453],
                            ['lat' => -34.92307146000943, 'lng' => -57.954220160117416],
                            ['lat' => -34.916893697063514, 'lng' => -57.94728009432522],
                            ['lat' => -34.91558341510686, 'lng' => -57.949162527718784]
                        ]
                    ],
                    'es_prohibido_estacionar' => false,
                    'activa' => true
                ],
                'horarios' => [
                    ['dia_semana' => 'lunes', 'hora_inicio' => '07:00:00', 'hora_fin' => '20:00:00'],
                    ['dia_semana' => 'martes', 'hora_inicio' => '07:00:00', 'hora_fin' => '20:00:00'],
                    ['dia_semana' => 'miercoles', 'hora_inicio' => '07:00:00', 'hora_fin' => '20:00:00'],
                    ['dia_semana' => 'jueves', 'hora_inicio' => '07:00:00', 'hora_fin' => '20:00:00'],
                    ['dia_semana' => 'viernes', 'hora_inicio' => '07:00:00', 'hora_fin' => '20:00:00']
                ]
            ],

            // ZONA PROHIBIDA - Prohibido estacionar (2 polígonos)
            [
                'zona' => [
                    'nombre' => 'Zona Prohibida',
                    'descripcion' => 'Zona de prohibición de estacionamiento',
                    'color_mapa' => '#0d0d0d',
                    'poligono_coordenadas' => [
                        // Polígono 1
                        [
                            ['lat' => -34.91124323438609, 'lng' => -57.954769311440245],
                            ['lat' => -34.91697591835763, 'lng' => -57.94721790610541],
                            ['lat' => -34.916629959173925, 'lng' => -57.94691459465379],
                            ['lat' => -34.91105330646013, 'lng' => -57.954604892600926],
                            ['lat' => -34.91124323438609, 'lng' => -57.954769311440245]
                        ],
                        // Polígono 2
                        [
                            ['lat' => -34.91402273767578, 'lng' => -57.95324578091423],
                            ['lat' => -34.9163870834124, 'lng' => -57.95009003682273],
                            ['lat' => -34.91630401756336, 'lng' => -57.94999273858244],
                            ['lat' => -34.913934946194544, 'lng' => -57.95317949965846],
                            ['lat' => -34.91402273767578, 'lng' => -57.95324578091423]
                        ]
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
            ],

            // ZONA DE PRUEBA - Para testing de la aplicación
            [
                'zona' => [
                    'nombre' => 'Zona de Prueba',
                    'descripcion' => 'Zona de prueba para desarrollo y testing - Disponible 24/7',
                    'color_mapa' => '#ff6b35',
                    'poligono_coordenadas' => [
                        // Polígono actualizado con las nuevas coordenadas
                        [
                            ['lat' => -34.889592732606864, 'lng' => -58.01924667103398],
                            ['lat' => -34.8922776617411, 'lng' => -58.021433626040206],
                            ['lat' => -34.89470532147908, 'lng' => -58.01804721418459],
                            ['lat' => -34.8909618432272, 'lng' => -58.01382388242274],
                            ['lat' => -34.889592732606864, 'lng' => -58.01924667103398]
                        ]
                    ],
                    'es_prohibido_estacionar' => false,
                    'activa' => true
                ],
                'horarios' => [
                    ['dia_semana' => 'lunes', 'hora_inicio' => '07:00:00', 'hora_fin' => '20:00:00'],
                    ['dia_semana' => 'martes', 'hora_inicio' => '07:00:00', 'hora_fin' => '20:00:00'],
                    ['dia_semana' => 'miercoles', 'hora_inicio' => '07:00:00', 'hora_fin' => '20:00:00'],
                    ['dia_semana' => 'jueves', 'hora_inicio' => '07:00:00', 'hora_fin' => '20:00:00'],
                    ['dia_semana' => 'viernes', 'hora_inicio' => '07:00:00', 'hora_fin' => '20:00:00'],
                    ['dia_semana' => 'sabado', 'hora_inicio' => '09:00:00', 'hora_fin' => '14:00:00']
                ]
            ],

            // ZONA DE PRUEBA 2 - Para testing adicional de la aplicación
            [
                'zona' => [
                    'nombre' => 'Zona de Prueba 2',
                    'descripcion' => 'Segunda zona de prueba para desarrollo y testing - Zona paga de lunes a viernes',
                    'color_mapa' => '#8a2be2', // Color violeta
                    'poligono_coordenadas' => [
                        // Polígono con las nuevas coordenadas (convertidas de lng,lat a lat,lng)
                        [
                            ['lat' => -34.912141610626094, 'lng' => -57.96710502610871],
                            ['lat' => -34.91495896942417, 'lng' => -57.970122705486204],
                            ['lat' => -34.917508258028086, 'lng' => -57.96680200435807],
                            ['lat' => -34.914533243935544, 'lng' => -57.963526810754814],
                            ['lat' => -34.912141610626094, 'lng' => -57.96710502610871]
                        ]
                    ],
                    'es_prohibido_estacionar' => false,
                    'activa' => true
                ],
                'horarios' => [
                    ['dia_semana' => 'lunes', 'hora_inicio' => '08:00:00', 'hora_fin' => '11:40:00'],
                    ['dia_semana' => 'martes', 'hora_inicio' => '08:00:00', 'hora_fin' => '11:40:00'],
                    ['dia_semana' => 'miercoles', 'hora_inicio' => '08:00:00', 'hora_fin' => '11:40:00'],
                    ['dia_semana' => 'jueves', 'hora_inicio' => '08:00:00', 'hora_fin' => '11:40:00'],
                    ['dia_semana' => 'viernes', 'hora_inicio' => '08:00:00', 'hora_fin' => '11:40:00']
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

        $this->command->info('Se han creado ' . count($zonasData) . ' zonas con múltiples polígonos exitosamente.');
    }
}
