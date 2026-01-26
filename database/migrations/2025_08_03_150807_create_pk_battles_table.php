<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::create('pk_battles', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('live_stream_id')->constrained('agora_channels')->onDelete('cascade');
            $table->uuid('challenger_id');
            $table->uuid('opponent_id');
            $table->enum('status', ['pending', 'active', 'finished', 'cancelled'])->default('pending');
            $table->integer('challenger_score')->default(0);
            $table->integer('opponent_score')->default(0);
            $table->uuid('winner_id')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            $table->foreign('challenger_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('opponent_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('winner_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pk_battles');
    }
};
