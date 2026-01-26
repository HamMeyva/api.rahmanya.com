<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public $withinTransaction = false;

    public function up(): void
    {
        try {
            echo "🔧 Updating pk_battles enum values to uppercase...\n";

            // Status enum
            DB::statement("ALTER TABLE pk_battles DROP CONSTRAINT IF EXISTS pk_battles_status_check");
            DB::statement("UPDATE pk_battles SET status = UPPER(status)");
            DB::statement("ALTER TABLE pk_battles ADD CONSTRAINT pk_battles_status_check 
                          CHECK (status IN ('PENDING', 'ACTIVE', 'FINISHED', 'CANCELLED'))");

            // Battle phase enum (if exists)
            $hasColumn = DB::select("SELECT COUNT(*) as count FROM information_schema.columns 
                                   WHERE table_name = 'pk_battles' AND column_name = 'battle_phase'");
            if ($hasColumn[0]->count > 0) {
                DB::statement("ALTER TABLE pk_battles DROP CONSTRAINT IF EXISTS pk_battles_battle_phase_check");
                DB::statement("UPDATE pk_battles SET battle_phase = UPPER(battle_phase) WHERE battle_phase IS NOT NULL");
                DB::statement("ALTER TABLE pk_battles ADD CONSTRAINT pk_battles_battle_phase_check 
                              CHECK (battle_phase IN ('COUNTDOWN', 'ACTIVE', 'PAUSED', 'ENDED'))");
            }

            // Stream status enums (if exist) - önce constraint'leri kaldır
            $hasColumn = DB::select("SELECT COUNT(*) as count FROM information_schema.columns 
                                   WHERE table_name = 'pk_battles' AND column_name = 'challenger_stream_status'");
            if ($hasColumn[0]->count > 0) {
                // Önce tüm stream status constraint'lerini kaldır
                DB::statement("ALTER TABLE pk_battles DROP CONSTRAINT IF EXISTS pk_battles_challenger_stream_status_check");
                DB::statement("ALTER TABLE pk_battles DROP CONSTRAINT IF EXISTS pk_battles_opponent_stream_status_check");

                // Sonra değerleri update et
                DB::statement("UPDATE pk_battles SET challenger_stream_status = UPPER(challenger_stream_status) 
                              WHERE challenger_stream_status IS NOT NULL");
                DB::statement("UPDATE pk_battles SET opponent_stream_status = UPPER(opponent_stream_status) 
                              WHERE opponent_stream_status IS NOT NULL");

                // Constraint'leri yeniden ekle
                DB::statement("ALTER TABLE pk_battles ADD CONSTRAINT pk_battles_challenger_stream_status_check 
                              CHECK (challenger_stream_status IN ('CONNECTED', 'DISCONNECTED', 'RECONNECTING'))");
                DB::statement("ALTER TABLE pk_battles ADD CONSTRAINT pk_battles_opponent_stream_status_check 
                              CHECK (opponent_stream_status IN ('CONNECTED', 'DISCONNECTED', 'RECONNECTING'))");
            }

            echo "✅ pk_battles enum values updated to uppercase\n";

        } catch (\Exception $e) {
            echo "❌ Error updating enum values: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    public function down(): void
    {
        try {
            // Status enum
            DB::statement("ALTER TABLE pk_battles DROP CONSTRAINT IF EXISTS pk_battles_status_check");
            DB::statement("UPDATE pk_battles SET status = LOWER(status)");
            DB::statement("ALTER TABLE pk_battles ADD CONSTRAINT pk_battles_status_check 
                          CHECK (status IN ('pending', 'active', 'finished', 'cancelled'))");

            // Battle phase enum (if exists)
            $hasColumn = DB::select("SELECT COUNT(*) as count FROM information_schema.columns 
                                   WHERE table_name = 'pk_battles' AND column_name = 'battle_phase'");
            if ($hasColumn[0]->count > 0) {
                DB::statement("ALTER TABLE pk_battles DROP CONSTRAINT IF EXISTS pk_battles_battle_phase_check");
                DB::statement("UPDATE pk_battles SET battle_phase = LOWER(battle_phase) WHERE battle_phase IS NOT NULL");
                DB::statement("ALTER TABLE pk_battles ADD CONSTRAINT pk_battles_battle_phase_check 
                              CHECK (battle_phase IN ('countdown', 'active', 'paused', 'ended'))");
            }

            // Stream status enums (if exist)
            $hasColumn = DB::select("SELECT COUNT(*) as count FROM information_schema.columns 
                                   WHERE table_name = 'pk_battles' AND column_name = 'challenger_stream_status'");
            if ($hasColumn[0]->count > 0) {
                // Önce constraint'leri kaldır
                DB::statement("ALTER TABLE pk_battles DROP CONSTRAINT IF EXISTS pk_battles_challenger_stream_status_check");
                DB::statement("ALTER TABLE pk_battles DROP CONSTRAINT IF EXISTS pk_battles_opponent_stream_status_check");

                // Değerleri lowercase'e çevir
                DB::statement("UPDATE pk_battles SET challenger_stream_status = LOWER(challenger_stream_status) 
                              WHERE challenger_stream_status IS NOT NULL");
                DB::statement("UPDATE pk_battles SET opponent_stream_status = LOWER(opponent_stream_status) 
                              WHERE opponent_stream_status IS NOT NULL");

                // Eski constraint'leri ekle
                DB::statement("ALTER TABLE pk_battles ADD CONSTRAINT pk_battles_challenger_stream_status_check 
                              CHECK (challenger_stream_status IN ('connected', 'disconnected', 'reconnecting'))");
                DB::statement("ALTER TABLE pk_battles ADD CONSTRAINT pk_battles_opponent_stream_status_check 
                              CHECK (opponent_stream_status IN ('connected', 'disconnected', 'reconnecting'))");
            }

            echo "✅ pk_battles enum values reverted to lowercase\n";

        } catch (\Exception $e) {
            echo "❌ Error reverting enum values: " . $e->getMessage() . "\n";
        }
    }
};