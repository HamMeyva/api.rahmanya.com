<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public $withinTransaction = false;

    public function up(): void
    {
        try {
            echo "🔧 Cleaning invalid PK battles data...\n";

            // pk_battles tablosu UUID tipinde doğru, sadece geçersiz verileri temizleyelim
            // Numeric değerleri UUID formatına çevirmeye çalışalım veya silelim
            $count = DB::table('pk_battles')->count();
            echo "Total PK battles before cleanup: {$count}\n";

            // Geçersiz UUID formatındaki verileri temizle
            $deleted = DB::table('pk_battles')
                ->where(function ($query) {
                    $query->whereRaw("challenger_id::text !~ '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$'")
                        ->orWhereRaw("opponent_id::text !~ '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$'");
                })
                ->delete();

            echo "Deleted {$deleted} invalid PK battles\n";

            $remainingCount = DB::table('pk_battles')->count();
            echo "Remaining PK battles: {$remainingCount}\n";

            echo "✅ PK battles data cleanup completed successfully\n";

        } catch (\Exception $e) {
            echo "❌ Error cleaning PK battles data: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    public function down(): void
    {
        // Bu migration sadece geçersiz verileri temizliyor, geri alınacak bir şey yok
        echo "✅ PK battles cleanup migration - nothing to rollback\n";
    }
};