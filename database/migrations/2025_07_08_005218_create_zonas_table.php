<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zonas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->json('poligono_coordenadas');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->json('dias_habilitados'); 
            $table->decimal('tarifa_por_hora', 8, 2);
            $table->string('color_mapa')->default('#0000FF'); // Color en formato hexadecimal
            $table->boolean('es_prohibido_estacionar')->default(false);
            $table->boolean('activa')->default(true);
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zonas');
    }
};