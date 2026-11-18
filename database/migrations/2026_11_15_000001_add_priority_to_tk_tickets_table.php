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
        Schema::table('TK_tickets', function (Blueprint $table) {
            $table->enum('priority', ['BAJA', 'MEDIA', 'ALTA'])
                ->default('MEDIA')
                ->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('TK_tickets', function (Blueprint $table) {
            $table->dropColumn('priority');
        });
    }
};

