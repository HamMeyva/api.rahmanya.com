<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::create('live_stream_participants', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('live_stream_id')->constrained('agora_channels')->onDelete('cascade');
            $table->uuid('user_id');
            $table->enum('role', ['host', 'guest', 'viewer'])->default('viewer');
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('left_at')->nullable();
            $table->boolean('is_active')->default(true);

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['live_stream_id', 'user_id'], 'unique_participant');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_stream_participants');
    }
};
