<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_activity_logs', function (Blueprint $table): void {
            $table->bigIncrements('user_activity_log_id');
            $table->integer('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('method', 10);
            $table->string('route_name')->nullable();
            $table->text('request_path');
            $table->unsignedSmallInteger('status_code');
            $table->unsignedInteger('duration_ms');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id')
                ->references('user_id')
                ->on('user')
                ->nullOnDelete();
            $table->index(['user_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_activity_logs');
    }
};
