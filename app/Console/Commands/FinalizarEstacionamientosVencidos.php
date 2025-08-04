<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Implementation\EstacionamientoService;

class FinalizarEstacionamientosVencidos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'estacionamientos:finalizar-vencidos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Finaliza los estacionamientos en zonas cuyo horario de operación ha concluido.';

    protected $estacionamientoService;

    /**
     * Execute the console command.
     */
    public function __construct(EstacionamientoService $estacionamientoService)
    {
        parent::__construct();
        $this->estacionamientoService = $estacionamientoService;
    }

    public function handle()
    {
        $this->info('Verificando estacionamientos vencidos...');
        $resultado = $this->estacionamientoService->finalizarEstacionamientosVencidos();
        $this->info($resultado['message']);
        return 0;
    }
}
