<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('TK_tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('TK_tickets', 'ai_recommendations')) {
                $table->jsonb('ai_recommendations')->nullable()->after('level1_diagnostic');
            }
        });
    }

    public function down(): void
    {
        Schema::table('TK_tickets', function (Blueprint $table) {
            if (Schema::hasColumn('TK_tickets', 'ai_recommendations')) {
                $table->dropColumn('ai_recommendations');
            }
        });
    }
};