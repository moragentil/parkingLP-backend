<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Interface\VehiculoServiceInterface;
use App\Services\Implementation\VehiculoService;
use App\Services\Interface\EstacionamientoServiceInterface;
use App\Services\Implementation\EstacionamientoService;
use App\Services\Interface\ZonaServiceInterface;
use App\Services\Implementation\ZonaService;
use App\Services\Interface\AlarmaServiceInterface;
use App\Services\Implementation\AlarmaService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(VehiculoServiceInterface::class, VehiculoService::class);
        $this->app->bind(EstacionamientoServiceInterface::class, EstacionamientoService::class);
        $this->app->bind(ZonaServiceInterface::class, ZonaService::class);
        $this->app->bind(AlarmaServiceInterface::class, AlarmaService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
