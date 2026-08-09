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
        Schema::table('transaksi', function (Blueprint $table) {
            $table->enum('metode_pembayaran', ['midtrans', 'manual'])->nullable()->after('layanan_id');
            $table->string('bukti_transfer')->nullable()->after('midtrans_payload');
            $table->string('nama_pengirim')->nullable()->after('bukti_transfer');
            $table->string('bank_pengirim')->nullable()->after('nama_pengirim');
            $table->text('catatan_admin')->nullable()->after('bank_pengirim');
            $table->unsignedBigInteger('verified_by')->nullable()->after('catatan_admin');
            $table->timestamp('verified_at')->nullable()->after('verified_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};