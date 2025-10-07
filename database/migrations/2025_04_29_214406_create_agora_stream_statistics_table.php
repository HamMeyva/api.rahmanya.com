<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('agora_stream_statistics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agora_channel_id');
            $table->uuid('user_id');
            $table->date('date');
            $table->integer('total_stream_duration')->default(0);
            $table->integer('total_viewers')->default(0);
            $table->integer('unique_viewers')->default(0);
            $table->integer('max_concurrent_viewers')->default(0);
            $table->integer('avg_watch_time')->default(0);
            $table->integer('total_comments')->default(0);
            $table->integer('total_likes')->default(0);
            $table->integer('total_gifts')->default(0);
            $table->integer('total_coins_earned')->default(0);
            $table->integer('new_followers_gained')->default(0);
            $table->timestampsTz();

            $table->foreign('agora_channel_id')
                  ->references('id')
                  ->on('agora_channels')
                  ->onDelete('cascade');

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->unique(['agora_channel_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agora_stream_statistics');
    }
};
