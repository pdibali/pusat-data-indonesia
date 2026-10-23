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
        Schema::create('produsen_data', function (Blueprint $table) {
            $table->integer('produsen_id')->autoIncrement();
            $table->string('nama_produsen', 255);
            $table->string('email', 100)->nullable();
            $table->string('nama_contact_person', 100)->nullable();
            $table->string('kontak', 100)->nullable();
            $table->string('alamat', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produsen_data');
    }
};