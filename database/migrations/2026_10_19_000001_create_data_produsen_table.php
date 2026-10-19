<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_produsen', function (Blueprint $table) {
            $table->integer('data_id');
            $table->integer('produsen_id');
            $table->primary(['data_id', 'produsen_id']);
            $table->foreign('data_id')->references('id')->on('data')->cascadeOnDelete();
            $table->foreign('produsen_id')->references('produsen_id')->on('produsen_data')->cascadeOnDelete();
        });

        DB::table('data')
            ->whereNotNull('produsen_id')
            ->orderBy('id')
            ->select(['id', 'produsen_id'])
            ->chunk(500, function ($rows): void {
                DB::table('data_produsen')->insert(
                    $rows->map(fn ($row) => [
                        'data_id' => $row->id,
                        'produsen_id' => $row->produsen_id,
                    ])->all()
                );
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_produsen');
    }
};
