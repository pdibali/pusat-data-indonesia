<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProdusenIdToDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumn('data', 'produsen_id')) {
            Schema::table('data', function (Blueprint $table) {
                $table->unsignedBigInteger('produsen_id')->nullable();
            });
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
            Schema::table('data', function (Blueprint $table) {
                $table->dropColumn('produsen_id');
            });
        }
    }
}
