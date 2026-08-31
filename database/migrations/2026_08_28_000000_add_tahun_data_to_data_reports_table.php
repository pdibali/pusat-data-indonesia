<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_reports', function (Blueprint $table) {
            $table->string('tahun_data', 50)->nullable()->after('produsen_data');
        });

        DB::table('data_reports')->whereNull('tahun_data')->update(['tahun_data' => 'Tidak diketahui']);

        Schema::table('data_reports', function (Blueprint $table) {
            $table->string('tahun_data', 50)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('data_reports', function (Blueprint $table) {
            $table->dropColumn('tahun_data');
        });
    }
};