<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estacionamientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->onDelete('cascade');
            $table->foreignId('zona_id')->nullable()->constrained('zonas')->onDelete('set null');
            $table->date('fecha_inicio');
            $table->time('hora_inicio');
            $table->date('fecha_fin')->nullable();
            $table->time('hora_fin')->nullable();
            $table->enum('estado', ['activo', 'finalizado', 'vencido', 'cancelado'])->default('activo');
            $table->boolean('alarma_programada')->default(false);
            $table->decimal('monto_pagado', 8, 2)->default(0);
            $table->string('sem_transaction_id')->nullable();
            $table->decimal('latitud', 10, 8);
            $table->decimal('longitud', 11, 8);
            $table->string('direccion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estacionamientos');
    }
};