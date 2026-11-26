<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('TK_historical_cases', function (Blueprint $table) {
            if (!Schema::hasColumn('TK_historical_cases', 'category_id')) {
                $table->unsignedBigInteger('category_id')->nullable()->after('ticket_id');
                $table->foreign('category_id')
                      ->references('id')
                      ->on('TK_categories')
                      ->nullOnDelete();
            }
        });

        // Asignar category_id SOLO a los casos históricos que ya tienen un ticket_id real en TK_tickets
        // Los casos con ticket_id IS NULL quedan con category_id = NULL sin suponer categoría
        DB::statement('
            UPDATE "TK_historical_cases" h
            SET category_id = t.category_id
            FROM "TK_tickets" t
            WHERE h.ticket_id = t.id
              AND h.category_id IS NULL
        ');
    }

    public function down(): void
    {
        Schema::table('TK_historical_cases', function (Blueprint $table) {
            if (Schema::hasColumn('TK_historical_cases', 'category_id')) {
                $table->dropForeign(['category_id']);
                $table->dropColumn('category_id');
            }
        });
    }
};