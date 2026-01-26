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
            // Add pk_battle_id column to gift_transactions if it doesn't exist
            \DB::statement('ALTER TABLE gift_transactions ADD COLUMN IF NOT EXISTS pk_battle_id BIGINT NULL');

            // Add index for faster PK battle gift queries
            \DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS gift_transactions_pk_battle_id_index ON gift_transactions(pk_battle_id)');

            echo "✅ Added pk_battle_id column and index to gift_transactions\n";
        } catch (\Exception $e) {
            echo "ℹ️ gift_transactions pk_battle_id: " . $e->getMessage() . "\n";
        }
    }

    public function down()
    {
        try {
            \DB::statement('DROP INDEX CONCURRENTLY IF EXISTS gift_transactions_pk_battle_id_index');
            \DB::statement('ALTER TABLE gift_transactions DROP COLUMN IF EXISTS pk_battle_id');
        } catch (\Exception $e) {
            echo "ℹ️ Rollback failed: " . $e->getMessage() . "\n";
        }
    }
};
