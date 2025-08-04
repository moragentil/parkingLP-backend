<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alarmas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estacionamiento_id')->constrained()->onDelete('cascade');
            $table->date('fecha_alarma');
            $table->time('hora_alarma');
            $table->string('mensaje');
            $table->boolean('activa')->default(true);
            $table->boolean('enviada')->default(false);
            $table->enum('tipo', ['vencimiento', 'recordatorio', 'multa'])->default('vencimiento');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alarmas');
    }
};