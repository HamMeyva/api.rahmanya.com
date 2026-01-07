<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::table('pk_battles', function (Blueprint $table) {
            // Enhanced timer and synchronization fields
            if (!Schema::hasColumn('pk_battles', 'countdown_duration')) {
                $table->integer('countdown_duration')->default(10)->after('duration_seconds');
            }
            if (!Schema::hasColumn('pk_battles', 'countdown_started_at')) {
                $table->timestamp('countdown_started_at')->nullable()->after('countdown_duration');
            }
            if (!Schema::hasColumn('pk_battles', 'server_sync_time')) {
                $table->timestamp('server_sync_time')->nullable()->after('countdown_started_at');
            }
            
            // Enhanced battle state tracking
            if (!Schema::hasColumn('pk_battles', 'battle_phase')) {
                $table->enum('battle_phase', ['countdown', 'active', 'paused', 'ended'])->default('countdown')->after('status');
            }
            if (!Schema::hasColumn('pk_battles', 'last_activity_at')) {
                $table->timestamp('last_activity_at')->nullable()->after('server_sync_time');
            }
            
            // Gift tracking and progress
            if (!Schema::hasColumn('pk_battles', 'challenger_gift_count')) {
                $table->integer('challenger_gift_count')->default(0)->after('challenger_score');
            }
            if (!Schema::hasColumn('pk_battles', 'opponent_gift_count')) {
                $table->integer('opponent_gift_count')->default(0)->after('opponent_score');
            }
            if (!Schema::hasColumn('pk_battles', 'total_gift_value')) {
                $table->integer('total_gift_value')->default(0)->after('opponent_gift_count');
            }
            
            // Video stream state tracking
            if (!Schema::hasColumn('pk_battles', 'challenger_stream_status')) {
                $table->enum('challenger_stream_status', ['connected', 'disconnected', 'reconnecting'])->default('disconnected')->after('total_gift_value');
            }
            if (!Schema::hasColumn('pk_battles', 'opponent_stream_status')) {
                $table->enum('opponent_stream_status', ['connected', 'disconnected', 'reconnecting'])->default('disconnected')->after('challenger_stream_status');
            }
            
            // Battle configuration and metadata
            if (!Schema::hasColumn('pk_battles', 'battle_config')) {
                $table->json('battle_config')->nullable()->after('opponent_stream_status');
            }
            if (!Schema::hasColumn('pk_battles', 'error_logs')) {
                $table->json('error_logs')->nullable()->after('battle_config');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pk_battles', function (Blueprint $table) {
            $columnsToRemove = [
                'countdown_duration',
                'countdown_started_at', 
                'server_sync_time',
                'battle_phase',
                'last_activity_at',
                'challenger_gift_count',
                'opponent_gift_count',
                'total_gift_value',
                'challenger_stream_status',
                'opponent_stream_status',
                'battle_config',
                'error_logs'
            ];
            
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('pk_battles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};