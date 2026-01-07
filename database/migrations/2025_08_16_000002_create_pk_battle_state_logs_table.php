<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::create('pk_battle_state_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pk_battle_id')->constrained('pk_battles')->onDelete('cascade');
            $table->enum('event_type', [
                'battle_created',
                'countdown_started', 
                'battle_started',
                'gift_received',
                'score_updated',
                'timer_synced',
                'stream_connected',
                'stream_disconnected',
                'battle_paused',
                'battle_resumed',
                'battle_ended',
                'error_occurred'
            ]);
            $table->json('event_data')->nullable();
            $table->uuid('user_id')->nullable();
            $table->timestamp('server_timestamp');
            $table->timestamp('client_timestamp')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['pk_battle_id', 'event_type']);
            $table->index(['server_timestamp']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pk_battle_state_logs');
    }
};