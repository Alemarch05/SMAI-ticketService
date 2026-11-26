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
        Schema::table('TK_historical_cases', function (Blueprint $table) {
            $table->string('standard_solution')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('TK_historical_cases', function (Blueprint $table) {
            $table->dropColumn('standard_solution');
        });
    }
};
