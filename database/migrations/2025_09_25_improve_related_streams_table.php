<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            // Check if table exists
            $tableExists = DB::select("
                SELECT EXISTS (
                    SELECT FROM information_schema.tables
                    WHERE table_schema = 'public'
                    AND table_name = 'related_streams'
                )
            ");

            if (!$tableExists[0]->exists) {
                // Create the table if it doesn't exist
                DB::statement("
                    CREATE TABLE related_streams (
                        id SERIAL PRIMARY KEY,
                        host_stream_id VARCHAR(100) NOT NULL,
                        cohost_stream_id VARCHAR(100) NOT NULL,
                        cohost_user_id VARCHAR(100),
                        is_active BOOLEAN DEFAULT true,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        UNIQUE(host_stream_id, cohost_stream_id)
                    )
                ");

                // Create indexes
                DB::statement('CREATE INDEX idx_related_streams_host ON related_streams(host_stream_id)');
                DB::statement('CREATE INDEX idx_related_streams_cohost ON related_streams(cohost_stream_id)');
                DB::statement('CREATE INDEX idx_related_streams_active ON related_streams(is_active)');

                echo "Created related_streams table\n";
            } else {
                // Get existing columns
                $columns = DB::select("
                    SELECT column_name
                    FROM information_schema.columns
                    WHERE table_name = 'related_streams'
                    AND table_schema = 'public'
                ");
                $existingColumns = array_column($columns, 'column_name');

                // Add cohost_user_id if not exists
                if (!in_array('cohost_user_id', $existingColumns)) {
                    DB::statement('ALTER TABLE related_streams ADD COLUMN cohost_user_id VARCHAR(100) DEFAULT NULL');
                    echo "Added cohost_user_id column\n";
                }

                // Add is_active if not exists
                if (!in_array('is_active', $existingColumns)) {
                    DB::statement('ALTER TABLE related_streams ADD COLUMN is_active BOOLEAN DEFAULT true');
                    DB::statement('CREATE INDEX idx_related_streams_active ON related_streams(is_active)');
                    echo "Added is_active column\n";
                }
            }

            echo "Migration completed successfully\n";

        } catch (\Exception $e) {
            echo "Migration error: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            $tableExists = DB::select("
                SELECT EXISTS (
                    SELECT FROM information_schema.tables
                    WHERE table_schema = 'public'
                    AND table_name = 'related_streams'
                )
            ");

            if ($tableExists[0]->exists) {
                // Drop indexes first
                DB::statement('DROP INDEX IF EXISTS idx_related_streams_active');
                DB::statement('DROP INDEX IF EXISTS idx_related_streams_cohost');
                DB::statement('DROP INDEX IF EXISTS idx_related_streams_host');

                // Drop table
                DB::statement('DROP TABLE IF EXISTS related_streams');
                echo "Dropped related_streams table\n";
            }

        } catch (\Exception $e) {
            echo "Rollback error: " . $e->getMessage() . "\n";
        }
    }
};