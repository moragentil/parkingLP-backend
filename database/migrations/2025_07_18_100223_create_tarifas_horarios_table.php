<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tarifas_horarios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // ej: 'Tarifa Matutina', 'Tarifa Vespertina'
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->decimal('precio_por_hora', 8, 2);
            $table->text('descripcion')->nullable();
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tarifas_horarios');
    }
};
