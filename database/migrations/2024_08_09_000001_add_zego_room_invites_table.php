<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up()
    {
        // Use raw PostgreSQL for maximum compatibility
        try {
            \DB::statement('
                CREATE TABLE IF NOT EXISTS zego_room_invites (
                    id BIGSERIAL PRIMARY KEY,
                    agora_channel_id VARCHAR(255) NOT NULL,
                    inviter_id BIGINT NOT NULL,
                    invitee_id BIGINT NOT NULL,
                    room_id VARCHAR(255) NOT NULL,
                    status VARCHAR(255) DEFAULT \'pending\',
                    notification_data JSONB NULL,
                    expires_at TIMESTAMP NOT NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL
                )
            ');
            
            echo "✅ Zego room invites table created or already exists\n";
        } catch (\Exception $e) {
            echo "ℹ️ Zego room invites table: " . $e->getMessage() . "\n";
        }
        
        // Add missing columns if they don't exist
        try {
            \DB::statement('ALTER TABLE zego_room_invites ADD COLUMN IF NOT EXISTS agora_channel_id VARCHAR(255)');
            \DB::statement('ALTER TABLE zego_room_invites ADD COLUMN IF NOT EXISTS inviter_id BIGINT');
            \DB::statement('ALTER TABLE zego_room_invites ADD COLUMN IF NOT EXISTS invitee_id BIGINT');
            \DB::statement('ALTER TABLE zego_room_invites ADD COLUMN IF NOT EXISTS room_id VARCHAR(255)');
            \DB::statement('ALTER TABLE zego_room_invites ADD COLUMN IF NOT EXISTS status VARCHAR(255) DEFAULT \'pending\'');
            \DB::statement('ALTER TABLE zego_room_invites ADD COLUMN IF NOT EXISTS notification_data JSONB NULL');
            \DB::statement('ALTER TABLE zego_room_invites ADD COLUMN IF NOT EXISTS expires_at TIMESTAMP');
            \DB::statement('ALTER TABLE zego_room_invites ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NULL');
            \DB::statement('ALTER TABLE zego_room_invites ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL');
            echo "✅ Added zego room invites columns\n";
        } catch (\Exception $e) {
            echo "ℹ️ Zego room invites columns: " . $e->getMessage() . "\n";
        }
        
        // Add indexes
        try {
            \DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS zego_room_invites_agora_channel_id_index ON zego_room_invites(agora_channel_id)');
            \DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS zego_room_invites_inviter_id_index ON zego_room_invites(inviter_id)');
            \DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS zego_room_invites_invitee_id_index ON zego_room_invites(invitee_id)');
            \DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS zego_room_invites_status_index ON zego_room_invites(status)');
            \DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS zego_room_invites_expires_at_index ON zego_room_invites(expires_at)');
            echo "✅ Added zego room invites indexes\n";
        } catch (\Exception $e) {
            echo "ℹ️ Zego room invites indexes: " . $e->getMessage() . "\n";
        }
    }

    public function down()
    {
        Schema::dropIfExists('zego_room_invites');
    }
};
