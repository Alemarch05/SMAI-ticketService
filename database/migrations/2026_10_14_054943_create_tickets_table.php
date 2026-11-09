<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('TK_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); 
            $table->unsignedBigInteger('assigned_to')->nullable(); 
            $table->unsignedBigInteger('category_id');
            
            // Nuevos ajustes para el formulario
            $table->string('location'); 
            $table->string('title')->nullable(); 
            $table->text('description')->nullable(); 
            
            $table->enum('status', [
                'PENDIENTE_DIAGNOSTICO',
                'ESCALADO',
                'EN_PROCESO',
                'RESUELTO',
                'CERRADO'
            ])->default('PENDIENTE_DIAGNOSTICO');
            
            $table->json('level1_diagnostic')->nullable(); 
            $table->string('cause')->nullable(); 
            $table->text('solution')->nullable(); 
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('TK_tickets');
    }
};
