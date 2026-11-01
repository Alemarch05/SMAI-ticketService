<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('TK_ticket_logs', function (Blueprint $table) {
            $table->id();
            // ID del ticket referenciado (si se borra el ticket, se limpian sus logs)
            $table->foreignId('ticket_id')->constrained('TK_tickets')->onDelete('cascade');
            // ID del usuario que realiza la accion (obtenido del JWT)
            $table->unsignedBigInteger('user_id');
            // Accion realizada (ej: 'CREACION', 'ESCALAMIENTO', 'ASIGNACION', 'CAMBIO_ESTADO', 'CIERRE')
            $table->string('action');
            // Comentario o detalle del cambio
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('TK_ticket_logs');
    }
};