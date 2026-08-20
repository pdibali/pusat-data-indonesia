<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_requests', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->foreign('user_id')->references('user_id')->on('user')->cascadeOnDelete();
            $table->string('nama_data');
            $table->bigInteger('location_id')->nullable();
            $table->foreign('location_id')->references('location_id')->on('location');
            $table->text('deskripsi');
            $table->string('instansi_perkiraan');
            $table->enum('status', ['diajukan', 'ditinjau', 'diterima', 'ditolak'])->default('diajukan');
            $table->text('admin_notes')->nullable();
            $table->integer('reviewed_by')->nullable();
            $table->foreign('reviewed_by')->references('user_id')->on('user')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_requests');
    }
};