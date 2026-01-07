<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public $withinTransaction = false;

    public function up(): void
    {
        try {
            echo "🔧 Cleaning pk_battles table before enum update...\n";

            // Mevcut test verilerini temizle - numeric ID'leri olan kayıtlar
            $deletedCount = DB::table('pk_battles')
                ->where(function ($query) {
                    $query->whereRaw("challenger_id::text ~ '^[0-9]+$'")
                        ->orWhereRaw("opponent_id::text ~ '^[0-9]+$'");
                })
                ->delete();

            echo "Deleted {$deletedCount} pk_battles with numeric IDs\n";

            // Geçersiz UUID formatındaki kayıtları da temizle
            $deletedCount2 = DB::table('pk_battles')
                ->whereRaw("challenger_id::text !~ '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$'")
                ->delete();

            echo "Deleted {$deletedCount2} pk_battles with invalid UUID format\n";

            echo "✅ pk_battles table cleaned successfully\n";

        } catch (\Exception $e) {
            echo "❌ Error cleaning pk_battles: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    public function down(): void
    {
        // Nothing to rollback - we're just cleaning invalid data
        echo "✅ Nothing to rollback - data cleanup migration\n";
    }
};