<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Remove CRC32 Hashing from PK Battles
 *
 * This migration removes the CRC32 hashing workaround that was used to convert
 * MongoDB ObjectIds to numeric IDs for PostgreSQL. The system will now use
 * VARCHAR fields to store the original MongoDB ObjectIds directly.
 *
 * Changes:
 * 1. Truncate pk_battles table (clean slate for new ID format)
 * 2. Drop original_live_stream_id, original_challenger_id, original_opponent_id columns
 * 3. Convert live_stream_id from BIGINT to VARCHAR(255) if needed
 * 4. Convert challenger_id from BIGINT to VARCHAR(255)
 * 5. Convert opponent_id from BIGINT to VARCHAR(255)
 * 6. Convert winner_id from BIGINT to VARCHAR(255)
 * 7. Ensure no foreign key constraints exist on these columns
 *
 * IMPORTANT: This will delete all existing PK battle data!
 */
return new class extends Migration {
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            echo "🔧 Starting CRC32 removal migration for pk_battles...\n\n";

            // Step 1: Truncate pk_battles table (clean slate)
            echo "📌 Step 1: Truncating pk_battles table...\n";
            DB::statement('TRUNCATE TABLE pk_battles CASCADE');
            echo "✅ pk_battles table truncated\n\n";

            // Step 2: Drop original_* columns if they exist
            echo "📌 Step 2: Checking for original_* columns...\n";

            $columns = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'pk_battles'");
            $existingColumns = array_map(function ($col) {
                return $col->column_name;
            }, $columns);

            $originalColumns = ['original_live_stream_id', 'original_challenger_id', 'original_opponent_id'];

            foreach ($originalColumns as $column) {
                if (in_array($column, $existingColumns)) {
                    DB::statement("ALTER TABLE pk_battles DROP COLUMN IF EXISTS $column");
                    echo "✅ Dropped column: $column\n";
                } else {
                    echo "ℹ️  Column does not exist: $column\n";
                }
            }
            echo "\n";

            // Step 3: Convert live_stream_id to VARCHAR(255) if it's not already
            echo "📌 Step 3: Converting live_stream_id to VARCHAR(255)...\n";

            $liveStreamIdType = DB::select("
                SELECT data_type, character_maximum_length
                FROM information_schema.columns
                WHERE table_name = 'pk_battles'
                AND column_name = 'live_stream_id'
            ");

            if (!empty($liveStreamIdType)) {
                $currentType = $liveStreamIdType[0]->data_type;

                if ($currentType === 'bigint') {
                    // Drop foreign key constraint if exists
                    DB::statement('ALTER TABLE pk_battles DROP CONSTRAINT IF EXISTS pk_battles_live_stream_id_foreign');
                    echo "✅ Dropped foreign key constraint on live_stream_id (if existed)\n";

                    // Convert to VARCHAR
                    DB::statement('ALTER TABLE pk_battles ALTER COLUMN live_stream_id TYPE VARCHAR(255)');
                    echo "✅ Converted live_stream_id from BIGINT to VARCHAR(255)\n";
                } else {
                    echo "ℹ️  live_stream_id is already VARCHAR type\n";
                }
            }
            echo "\n";

            // Step 4: Convert challenger_id to VARCHAR(255)
            echo "📌 Step 4: Converting challenger_id to VARCHAR(255)...\n";

            // Drop foreign key constraint if exists
            DB::statement('ALTER TABLE pk_battles DROP CONSTRAINT IF EXISTS pk_battles_challenger_id_foreign');
            echo "✅ Dropped foreign key constraint on challenger_id (if existed)\n";

            // Convert to VARCHAR
            DB::statement('ALTER TABLE pk_battles ALTER COLUMN challenger_id TYPE VARCHAR(255)');
            echo "✅ Converted challenger_id from BIGINT to VARCHAR(255)\n\n";

            // Step 5: Convert opponent_id to VARCHAR(255)
            echo "📌 Step 5: Converting opponent_id to VARCHAR(255)...\n";

            // Drop foreign key constraint if exists
            DB::statement('ALTER TABLE pk_battles DROP CONSTRAINT IF EXISTS pk_battles_opponent_id_foreign');
            echo "✅ Dropped foreign key constraint on opponent_id (if existed)\n";

            // Convert to VARCHAR
            DB::statement('ALTER TABLE pk_battles ALTER COLUMN opponent_id TYPE VARCHAR(255)');
            echo "✅ Converted opponent_id from BIGINT to VARCHAR(255)\n\n";

            // Step 6: Convert winner_id to VARCHAR(255) and make it nullable
            echo "📌 Step 6: Converting winner_id to VARCHAR(255)...\n";

            // Drop foreign key constraint if exists
            DB::statement('ALTER TABLE pk_battles DROP CONSTRAINT IF EXISTS pk_battles_winner_id_foreign');
            echo "✅ Dropped foreign key constraint on winner_id (if existed)\n";

            // Convert to VARCHAR and ensure it's nullable
            DB::statement('ALTER TABLE pk_battles ALTER COLUMN winner_id TYPE VARCHAR(255)');
            DB::statement('ALTER TABLE pk_battles ALTER COLUMN winner_id DROP NOT NULL');
            echo "✅ Converted winner_id from BIGINT to VARCHAR(255)\n\n";

            // Step 7: Add indexes for performance (optional but recommended)
            echo "📌 Step 7: Adding indexes for performance...\n";

            DB::statement('CREATE INDEX IF NOT EXISTS idx_pk_battles_live_stream_id ON pk_battles(live_stream_id)');
            echo "✅ Created index on live_stream_id\n";

            DB::statement('CREATE INDEX IF NOT EXISTS idx_pk_battles_challenger_id ON pk_battles(challenger_id)');
            echo "✅ Created index on challenger_id\n";

            DB::statement('CREATE INDEX IF NOT EXISTS idx_pk_battles_opponent_id ON pk_battles(opponent_id)');
            echo "✅ Created index on opponent_id\n";

            DB::statement('CREATE INDEX IF NOT EXISTS idx_pk_battles_winner_id ON pk_battles(winner_id)');
            echo "✅ Created index on winner_id\n\n";

            echo "🎉 CRC32 removal migration completed successfully!\n";
            echo "ℹ️  The pk_battles table now uses VARCHAR(255) fields for all IDs\n";
            echo "ℹ️  MongoDB ObjectIds can now be stored directly without CRC32 hashing\n\n";

        } catch (\Exception $e) {
            echo "❌ Error during migration: " . $e->getMessage() . "\n";
            echo "Stack trace: " . $e->getTraceAsString() . "\n";
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     *
     * NOTE: This rollback will NOT restore the data that was truncated.
     * It only reverts the schema changes.
     */
    public function down(): void
    {
        try {
            echo "🔧 Rolling back CRC32 removal migration...\n\n";

            echo "📌 Converting ID columns back to BIGINT...\n";

            // Truncate again to avoid type conversion issues
            DB::statement('TRUNCATE TABLE pk_battles CASCADE');
            echo "✅ pk_battles table truncated\n";

            // Convert columns back to BIGINT
            DB::statement('ALTER TABLE pk_battles ALTER COLUMN live_stream_id TYPE BIGINT USING live_stream_id::BIGINT');
            echo "✅ Converted live_stream_id back to BIGINT\n";

            DB::statement('ALTER TABLE pk_battles ALTER COLUMN challenger_id TYPE BIGINT USING challenger_id::BIGINT');
            echo "✅ Converted challenger_id back to BIGINT\n";

            DB::statement('ALTER TABLE pk_battles ALTER COLUMN opponent_id TYPE BIGINT USING opponent_id::BIGINT');
            echo "✅ Converted opponent_id back to BIGINT\n";

            DB::statement('ALTER TABLE pk_battles ALTER COLUMN winner_id TYPE BIGINT USING winner_id::BIGINT');
            echo "✅ Converted winner_id back to BIGINT\n";

            echo "\n📌 Re-adding original_* columns...\n";

            // Re-add original_* columns
            DB::statement('ALTER TABLE pk_battles ADD COLUMN IF NOT EXISTS original_live_stream_id VARCHAR(255) NULL');
            echo "✅ Added original_live_stream_id column\n";

            DB::statement('ALTER TABLE pk_battles ADD COLUMN IF NOT EXISTS original_challenger_id VARCHAR(255) NULL');
            echo "✅ Added original_challenger_id column\n";

            DB::statement('ALTER TABLE pk_battles ADD COLUMN IF NOT EXISTS original_opponent_id VARCHAR(255) NULL');
            echo "✅ Added original_opponent_id column\n";

            echo "\n🎉 Rollback completed!\n";
            echo "⚠️  WARNING: Previous data was NOT restored. Only schema changes were reverted.\n\n";

        } catch (\Exception $e) {
            echo "❌ Error during rollback: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
};
