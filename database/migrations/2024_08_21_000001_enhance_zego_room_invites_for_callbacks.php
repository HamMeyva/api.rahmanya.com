<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up()
    {
        // Add missing columns to zego_room_invites table if they don't exist
        try {
            \DB::statement('ALTER TABLE zego_room_invites ADD COLUMN IF NOT EXISTS call_id VARCHAR(255) NULL');
            \DB::statement('ALTER TABLE zego_room_invites ADD COLUMN IF NOT EXISTS caller_id VARCHAR(255) NULL');
            \DB::statement('ALTER TABLE zego_room_invites ADD COLUMN IF NOT EXISTS callee_id VARCHAR(255) NULL');
            \DB::statement('ALTER TABLE zego_room_invites ADD COLUMN IF NOT EXISTS type VARCHAR(255) DEFAULT \'call\'');
            \DB::statement('ALTER TABLE zego_room_invites ADD COLUMN IF NOT EXISTS extra_info TEXT NULL');
            echo "✅ Added call-related columns to zego_room_invites\n";
        } catch (\Exception $e) {
            echo "ℹ️ Call-related columns: " . $e->getMessage() . "\n";
        }
        
        // Add timestamp columns
        try {
            \DB::statement('ALTER TABLE zego_room_invites ADD COLUMN IF NOT EXISTS sent_at TIMESTAMP NULL');
            \DB::statement('ALTER TABLE zego_room_invites ADD COLUMN IF NOT EXISTS cancelled_at TIMESTAMP NULL');
            \DB::statement('ALTER TABLE zego_room_invites ADD COLUMN IF NOT EXISTS accepted_at TIMESTAMP NULL');
            \DB::statement('ALTER TABLE zego_room_invites ADD COLUMN IF NOT EXISTS rejected_at TIMESTAMP NULL');
            \DB::statement('ALTER TABLE zego_room_invites ADD COLUMN IF NOT EXISTS timeout_at TIMESTAMP NULL');
            echo "✅ Added timestamp columns to zego_room_invites\n";
        } catch (\Exception $e) {
            echo "ℹ️ Timestamp columns: " . $e->getMessage() . "\n";
        }
        
        // Update status column to support more values (PostgreSQL way)
        try {
            // PostgreSQL doesn't support changing enum values easily, so we'll leave it as VARCHAR
            // which is more flexible anyway
            echo "ℹ️ Status column update skipped - using flexible VARCHAR instead of enum\n";
        } catch (\Exception $e) {
            echo "ℹ️ Status column update: " . $e->getMessage() . "\n";
        }
        
        // Add indexes
        try {
            \DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS zego_room_invites_call_id_index ON zego_room_invites(call_id)');
            \DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS zego_room_invites_caller_id_index ON zego_room_invites(caller_id)');
            \DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS zego_room_invites_callee_id_index ON zego_room_invites(callee_id)');
            \DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS zego_room_invites_type_index ON zego_room_invites(type)');
            \DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS zego_room_invites_type_status_index ON zego_room_invites(type, status)');
            echo "✅ Added enhanced zego room invites indexes\n";
        } catch (\Exception $e) {
            echo "ℹ️ Enhanced indexes: " . $e->getMessage() . "\n";
        }
    }

    public function down()
    {
        Schema::table('zego_room_invites', function (Blueprint $table) {
            // Remove added columns
            $table->dropColumn([
                'call_id', 'caller_id', 'callee_id', 'type', 'extra_info',
                'sent_at', 'cancelled_at', 'accepted_at', 'rejected_at', 'timeout_at'
            ]);
            
            // Remove added indexes
            $table->dropIndex(['zego_room_invites_call_id_index']);
            $table->dropIndex(['zego_room_invites_caller_id_callee_id_index']);
            $table->dropIndex(['zego_room_invites_type_status_index']);
            
            // Restore original status enum
            $table->enum('status', ['pending', 'accepted', 'rejected', 'expired'])
                  ->default('pending')->change();
        });
    }
};
