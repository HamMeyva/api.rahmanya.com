<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        if (Schema::hasTable('live_stream_participants')) {
            Schema::table('live_stream_participants', function (Blueprint $table) {
                if (!Schema::hasColumn('live_stream_participants', 'participant_type')) {
                    $table->enum('participant_type', ['host', 'co_host', 'guest', 'pk_opponent'])->default('guest')->after('role');
                }
                if (!Schema::hasColumn('live_stream_participants', 'zego_stream_id')) {
                    $table->string('zego_stream_id')->nullable()->after('participant_type');
                }
                if (!Schema::hasColumn('live_stream_participants', 'audio_enabled')) {
                    $table->boolean('audio_enabled')->default(true)->after('zego_stream_id');
                }
                if (!Schema::hasColumn('live_stream_participants', 'video_enabled')) {
                    $table->boolean('video_enabled')->default(true)->after('audio_enabled');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('live_stream_participants')) {
            Schema::table('live_stream_participants', function (Blueprint $table) {
                if (Schema::hasColumn('live_stream_participants', 'participant_type')) {
                    $table->dropColumn('participant_type');
                }
                if (Schema::hasColumn('live_stream_participants', 'zego_stream_id')) {
                    $table->dropColumn('zego_stream_id');
                }
                if (Schema::hasColumn('live_stream_participants', 'audio_enabled')) {
                    $table->dropColumn('audio_enabled');
                }
                if (Schema::hasColumn('live_stream_participants', 'video_enabled')) {
                    $table->dropColumn('video_enabled');
                }
            });
        }
    }
};


