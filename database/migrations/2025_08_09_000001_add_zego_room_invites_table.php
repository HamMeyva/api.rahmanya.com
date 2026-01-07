<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up()
    {
        if (!Schema::hasTable('zego_room_invites')) {
            Schema::create('zego_room_invites', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agora_channel_id')->constrained('agora_channels')->onDelete('cascade');
                $table->foreignId('inviter_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('invitee_id')->constrained('users')->onDelete('cascade');
                $table->string('room_id');
                $table->enum('status', ['pending', 'accepted', 'rejected', 'expired'])->default('pending');
                $table->json('notification_data')->nullable();
                $table->timestamp('expires_at');
                $table->timestamps();

                $table->index(['invitee_id', 'status']);
                $table->index(['agora_channel_id', 'status']);
                $table->index('expires_at');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('zego_room_invites');
    }
};
