<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anomalies', function (Blueprint $table) {
            $table->decimal('previous_value', 20, 4)->nullable()->change();
            $table->decimal('current_value', 20, 4)->nullable()->change();
            $table->decimal('percentage_change', 20, 4)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('anomalies', function (Blueprint $table) {
            $table->decimal('previous_value', 15, 4)->nullable()->change();
            $table->decimal('current_value', 15, 4)->nullable()->change();
            $table->decimal('percentage_change', 15, 4)->nullable()->change();
        });
    }
};