<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class RenameProdusenIdToSumberMetadataPertamaInMetadataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumn('metadata', 'sumber_metadata_pertama')) {
            Schema::table('metadata', function (Blueprint $table) {
                $table->unsignedBigInteger('sumber_metadata_pertama')->nullable();
            });
        }

        // Salin data dari produsen_id ke sumber_metadata_pertama jika kolom sumber sudah ada
        if (Schema::hasColumn('metadata', 'sumber_metadata_pertama') && Schema::hasColumn('metadata', 'produsen_id')) {
            DB::statement('UPDATE metadata SET sumber_metadata_pertama = produsen_id');
        }
        // Hapus foreign key terlebih dahulu jika ada, lalu hapus kolom
        if (Schema::hasColumn('metadata', 'produsen_id')) {
            try {
                Schema::table('metadata', function (Blueprint $table) {
                    $table->dropForeign(['produsen_id']);
                    $table->dropColumn('produsen_id');
                });
            } catch (\Exception $e) {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
                Schema::table('metadata', function (Blueprint $table) {
                    $table->dropColumn('produsen_id');
                });
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
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
        if (! Schema::hasColumn('metadata', 'produsen_id')) {
            Schema::table('metadata', function (Blueprint $table) {
                $table->unsignedBigInteger('produsen_id')->nullable();
            });
        }

        if (Schema::hasColumn('metadata', 'sumber_metadata_pertama') && Schema::hasColumn('metadata', 'produsen_id')) {
            DB::statement('UPDATE metadata SET produsen_id = sumber_metadata_pertama');
        }

        // Pasang kembali foreign key ke tabel produsen_data jika tabel tersebut ada
        try {
            Schema::table('metadata', function (Blueprint $table) {
                $table->foreign('produsen_id')->references('produsen_id')->on('produsen_data')->onDelete('set null');
            });
        } catch (\Exception $e) {
            // ignore jika tidak bisa membuat foreign key
        }

        Schema::table('metadata', function (Blueprint $table) {
            $table->dropColumn('sumber_metadata_pertama');
        });
    }
}
