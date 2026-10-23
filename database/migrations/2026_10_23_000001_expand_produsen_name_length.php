<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produsen_data', function (Blueprint $table) {
            $table->string('nama_produsen', 255)->change();
        });
    }

    public function down(): void
    {
        Schema::table('produsen_data', function (Blueprint $table) {
            $table->string('nama_produsen', 100)->change();
        });
    }
};
