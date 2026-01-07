<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if columns already exist
        $columns = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'pk_battles'");
        $existingColumns = array_map(function ($col) {
            return $col->column_name;
        }, $columns);

        // Add missing columns one by one to avoid transaction issues
        if (!in_array('battle_phase', $existingColumns)) {
            DB::statement('ALTER TABLE pk_battles ADD COLUMN battle_phase VARCHAR(50) NULL DEFAULT \'pending\'');
        }

        if (!in_array('countdown_duration', $existingColumns)) {
            DB::statement('ALTER TABLE pk_battles ADD COLUMN countdown_duration INTEGER NULL DEFAULT 10');
        }

        if (!in_array('countdown_started_at', $existingColumns)) {
            DB::statement('ALTER TABLE pk_battles ADD COLUMN countdown_started_at TIMESTAMP NULL');
        }

        if (!in_array('server_sync_time', $existingColumns)) {
            DB::statement('ALTER TABLE pk_battles ADD COLUMN server_sync_time TIMESTAMP NULL');
        }

        if (!in_array('last_activity_at', $existingColumns)) {
            DB::statement('ALTER TABLE pk_battles ADD COLUMN last_activity_at TIMESTAMP NULL');
        }

        if (!in_array('challenger_stream_status', $existingColumns)) {
            DB::statement('ALTER TABLE pk_battles ADD COLUMN challenger_stream_status VARCHAR(50) NULL DEFAULT \'disconnected\'');
        }

        if (!in_array('opponent_stream_status', $existingColumns)) {
            DB::statement('ALTER TABLE pk_battles ADD COLUMN opponent_stream_status VARCHAR(50) NULL DEFAULT \'disconnected\'');
        }

        if (!in_array('battle_config', $existingColumns)) {
            DB::statement('ALTER TABLE pk_battles ADD COLUMN battle_config JSONB NULL');
        }

        if (!in_array('error_logs', $existingColumns)) {
            DB::statement('ALTER TABLE pk_battles ADD COLUMN error_logs JSONB NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if columns exist before dropping
        $columns = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'pk_battles'");
        $existingColumns = array_map(function ($col) {
            return $col->column_name;
        }, $columns);

        $columnsToDrop = [
            'battle_phase',
            'countdown_duration',
            'countdown_started_at',
            'server_sync_time',
            'last_activity_at',
            'challenger_stream_status',
            'opponent_stream_status',
            'battle_config',
            'error_logs'
        ];

        foreach ($columnsToDrop as $column) {
            if (in_array($column, $existingColumns)) {
                DB::statement("ALTER TABLE pk_battles DROP COLUMN IF EXISTS $column");
            }
        }
    }
};