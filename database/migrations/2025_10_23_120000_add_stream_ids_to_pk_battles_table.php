<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public $withinTransaction = false;

    /**
     * Run the migrations.
     *
     * Adds opponent_stream_id and cohost_stream_ids fields to enable
     * multi-stream broadcasting for PK battles.
     */
    public function up(): void
    {
        // Check existing columns
        $columns = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'pk_battles'");
        $existingColumns = array_map(function ($col) {
            return $col->column_name;
        }, $columns);

        // Add opponent_stream_id field (for opponent's stream)
        if (!in_array('opponent_stream_id', $existingColumns)) {
            DB::statement('ALTER TABLE pk_battles ADD COLUMN opponent_stream_id VARCHAR(255) NULL');
            echo "✅ Added opponent_stream_id column\n";
        }

        // Add cohost_stream_ids field (JSON array for additional cohosts not in PK)
        if (!in_array('cohost_stream_ids', $existingColumns)) {
            DB::statement('ALTER TABLE pk_battles ADD COLUMN cohost_stream_ids JSONB NULL');
            echo "✅ Added cohost_stream_ids column\n";

            // Add GIN index for fast JSON queries (performance optimization)
            DB::statement('CREATE INDEX IF NOT EXISTS idx_pk_battles_cohost_stream_ids ON pk_battles USING GIN (cohost_stream_ids)');
            echo "✅ Created GIN index on cohost_stream_ids\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check existing columns
        $columns = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'pk_battles'");
        $existingColumns = array_map(function ($col) {
            return $col->column_name;
        }, $columns);

        // Drop opponent_stream_id
        if (in_array('opponent_stream_id', $existingColumns)) {
            DB::statement('ALTER TABLE pk_battles DROP COLUMN IF EXISTS opponent_stream_id');
            echo "✅ Dropped opponent_stream_id column\n";
        }

        // Drop cohost_stream_ids (and its index)
        if (in_array('cohost_stream_ids', $existingColumns)) {
            DB::statement('DROP INDEX IF EXISTS idx_pk_battles_cohost_stream_ids');
            echo "✅ Dropped GIN index on cohost_stream_ids\n";

            DB::statement('ALTER TABLE pk_battles DROP COLUMN IF EXISTS cohost_stream_ids');
            echo "✅ Dropped cohost_stream_ids column\n";
        }
    }
};
