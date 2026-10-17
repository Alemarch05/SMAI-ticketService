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
        Schema::create('TK_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); 
            $table->unsignedBigInteger('assigned_to')->nullable(); 
            
            // $table->foreignId('category_id')->constrained('TK_categories')->onDelete('restrict');
            $table->unsignedBigInteger('category_id');
            
            $table->string('title');
            $table->text('description'); 
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('TK_tickets');
    }
};
