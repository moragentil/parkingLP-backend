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
        Schema::table('zonas', function (Blueprint $table) {
            // Eliminar campos que ahora van en zona_horarios
            $table->dropColumn(['hora_inicio', 'hora_fin', 'dias_habilitados', 'tarifa_por_hora']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zonas', function (Blueprint $table) {
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->json('dias_habilitados');
            $table->decimal('tarifa_por_hora', 8, 2);
        });
    }
};
