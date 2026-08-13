<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::table('transaksi', function (Blueprint $table) {
                $table->string('status', 20)->default('pending')->change();
            });
        } else {
            // MySQL/MariaDB
            DB::statement("ALTER TABLE transaksi MODIFY status VARCHAR(20) NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::table('transaksi', function (Blueprint $table) {
                $table->enum('status', ['pending', 'success', 'failed', 'cancelled'])->default('pending')->change();
            });
        } else {
            DB::statement("ALTER TABLE transaksi MODIFY status ENUM('pending', 'success', 'failed', 'cancelled') NOT NULL DEFAULT 'pending'");
        }
    }
};