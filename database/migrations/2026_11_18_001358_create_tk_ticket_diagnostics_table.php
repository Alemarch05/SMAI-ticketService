<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('TK_ticket_diagnostics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')
                  ->unique()
                  ->constrained('TK_tickets')
                  ->onDelete('cascade');
            $table->unsignedBigInteger('category_id'); 
            $table->json('decisions');                 
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('TK_ticket_diagnostics');
    }
};