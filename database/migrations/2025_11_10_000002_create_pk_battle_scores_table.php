<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up()
    {
        try {
            \DB::statement('
                CREATE TABLE IF NOT EXISTS pk_battle_scores (
                    id BIGSERIAL PRIMARY KEY,
                    pk_battle_id BIGINT NOT NULL,
                    user_id VARCHAR(255) NOT NULL,
                    streamer_id VARCHAR(255) NOT NULL,
                    gift_id BIGINT NOT NULL,
                    gift_value INTEGER NOT NULL DEFAULT 0,
                    quantity INTEGER NOT NULL DEFAULT 1,
                    total_value INTEGER NOT NULL DEFAULT 0,
                    gift_transaction_id BIGINT NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL
                )
            ');

            echo "✅ pk_battle_scores table created or already exists\n";
        } catch (\Exception $e) {
            echo "ℹ️ pk_battle_scores table: " . $e->getMessage() . "\n";
        }

        // Add indexes for faster queries
        try {
            \DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS pk_battle_scores_pk_battle_id_index ON pk_battle_scores(pk_battle_id)');
            \DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS pk_battle_scores_user_id_index ON pk_battle_scores(user_id)');
            \DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS pk_battle_scores_streamer_id_index ON pk_battle_scores(streamer_id)');
            \DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS pk_battle_scores_created_at_index ON pk_battle_scores(created_at)');
            echo "✅ Added pk_battle_scores indexes\n";
        } catch (\Exception $e) {
            echo "ℹ️ pk_battle_scores indexes: " . $e->getMessage() . "\n";
        }
    }

    public function down()
    {
        Schema::dropIfExists('pk_battle_scores');
    }
};
