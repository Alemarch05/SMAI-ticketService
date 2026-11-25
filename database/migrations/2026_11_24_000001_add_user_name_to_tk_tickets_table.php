<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega el nombre digitado del solicitante al ticket.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('TK_tickets', 'user_name')) {
            Schema::table('TK_tickets', function (Blueprint $table) {
                $table->string('user_name')->nullable()->after('user_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('TK_tickets', 'user_name')) {
            Schema::table('TK_tickets', function (Blueprint $table) {
                $table->dropColumn('user_name');
            });
        }
    }
};