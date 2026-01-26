<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up()
    {
        // Add missing columns to agora_channels table if they don't exist
        try {
            \DB::statement('ALTER TABLE agora_channels ADD COLUMN IF NOT EXISTS zego_room_id VARCHAR(255) NULL');
            echo "✅ Added zego_room_id column to agora_channels\n";
        } catch (\Exception $e) {
            echo "ℹ️ Zego room id column: " . $e->getMessage() . "\n";
        }
        
        try {
            \DB::statement('ALTER TABLE agora_channels ADD COLUMN IF NOT EXISTS zego_enabled BOOLEAN DEFAULT true');
            echo "✅ Added zego_enabled column to agora_channels\n";
        } catch (\Exception $e) {
            echo "ℹ️ Zego enabled column: " . $e->getMessage() . "\n";
        }
        
        // Add indexes
        try {
            \DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS agora_channels_zego_room_id_index ON agora_channels(zego_room_id)');
            \DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS agora_channels_zego_enabled_index ON agora_channels(zego_enabled)');
            echo "✅ Added agora_channels zego indexes\n";
        } catch (\Exception $e) {
            echo "ℹ️ Agora channels zego indexes: " . $e->getMessage() . "\n";
        }
    }

    public function down()
    {
        Schema::table('agora_channels', function (Blueprint $table) {
            $table->dropColumn(['zego_room_id', 'zego_enabled']);
        });
    }
};
