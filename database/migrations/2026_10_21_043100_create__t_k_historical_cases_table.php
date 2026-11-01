<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('TK_historical_cases', function (Blueprint $table) {
            $table->id();
            // Puede estar asociado a un ticket cerrado o ser un caso base insertado manualmente
            $table->foreignId('ticket_id')->nullable()->constrained('TK_tickets')->onDelete('set null');
            $table->string('problem_type'); // Categoria o titulo corto del problema
            $table->string('cause');        // Causa raiz identificada
            $table->text('solution');       // Solucion que resolvio el problema
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('TK_historical_cases');
    }
};