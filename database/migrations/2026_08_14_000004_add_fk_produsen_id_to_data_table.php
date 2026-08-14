<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddFkProdusenIdToDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('data', 'produsen_id')) {
            try {
                Schema::table('data', function (Blueprint $table) {
                    $table->foreign('produsen_id')->references('produsen_id')->on('produsen_data')->onDelete('set null');
                });
            } catch (\Exception $e) {
                // Jika constraint sudah ada atau tidak bisa dibuat, lewati
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('data', 'produsen_id')) {
            try {
                Schema::table('data', function (Blueprint $table) {
                    $table->dropForeign(['produsen_id']);
                });
            } catch (\Exception $e) {
                // Jika gagal, coba nonaktifkan cek FK sementara
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
                try {
                    Schema::table('data', function (Blueprint $table) {
                        $table->dropForeign(['produsen_id']);
                    });
                } catch (\Exception $e) {
                    // ignore
                }
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        }
    }
}
