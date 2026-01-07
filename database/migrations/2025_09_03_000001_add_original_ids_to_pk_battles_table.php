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

        // Add columns one by one to avoid transaction issues
        if (!in_array('original_live_stream_id', $existingColumns)) {
            DB::statement('ALTER TABLE pk_battles ADD COLUMN original_live_stream_id VARCHAR(255) NULL');
        }

        if (!in_array('original_challenger_id', $existingColumns)) {
            DB::statement('ALTER TABLE pk_battles ADD COLUMN original_challenger_id VARCHAR(255) NULL');
        }

        if (!in_array('original_opponent_id', $existingColumns)) {
            DB::statement('ALTER TABLE pk_battles ADD COLUMN original_opponent_id VARCHAR(255) NULL');
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

        if (in_array('original_live_stream_id', $existingColumns)) {
            DB::statement('ALTER TABLE pk_battles DROP COLUMN IF EXISTS original_live_stream_id');
        }

        if (in_array('original_challenger_id', $existingColumns)) {
            DB::statement('ALTER TABLE pk_battles DROP COLUMN IF EXISTS original_challenger_id');
        }

        if (in_array('original_opponent_id', $existingColumns)) {
            DB::statement('ALTER TABLE pk_battles DROP COLUMN IF EXISTS original_opponent_id');
        }
    }
};