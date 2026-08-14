<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddFkSumberMetadataPertamaToMetadataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('metadata', 'sumber_metadata_pertama')) {
            try {
                Schema::table('metadata', function (Blueprint $table) {
                    $table->foreign('sumber_metadata_pertama')
                          ->references('produsen_id')
                          ->on('produsen_data')
                          ->onDelete('set null');
                });
            } catch (\Exception $e) {
                // ignore if FK already exists or cannot be created
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
        if (Schema::hasColumn('metadata', 'sumber_metadata_pertama')) {
            try {
                Schema::table('metadata', function (Blueprint $table) {
                    $table->dropForeign(['sumber_metadata_pertama']);
                });
            } catch (\Exception $e) {
                // try with FK checks disabled
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
                try {
                    Schema::table('metadata', function (Blueprint $table) {
                        $table->dropForeign(['sumber_metadata_pertama']);
                    });
                } catch (\Exception $_) {
                    // ignore
                }
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        }
    }
}
