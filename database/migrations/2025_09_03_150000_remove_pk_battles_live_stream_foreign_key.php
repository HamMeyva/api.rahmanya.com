<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public $withinTransaction = false;

    public function up(): void
    {
        try {
            echo "🔧 Removing foreign key constraint from pk_battles.live_stream_id...\n";

            // Foreign key constraint'i kaldır
            DB::statement('ALTER TABLE pk_battles DROP CONSTRAINT IF EXISTS pk_battles_live_stream_id_foreign');

            // live_stream_id'yi string yapalım ki Zego room ID'leri direkt saklayabilelim
            DB::statement('ALTER TABLE pk_battles ALTER COLUMN live_stream_id TYPE VARCHAR(255)');

            echo "✅ pk_battles.live_stream_id foreign key constraint removed and type changed to string\n";

        } catch (\Exception $e) {
            echo "❌ Error removing foreign key constraint: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    public function down(): void
    {
        try {
            // live_stream_id'yi tekrar bigint yap
            DB::statement('ALTER TABLE pk_battles ALTER COLUMN live_stream_id TYPE BIGINT USING live_stream_id::BIGINT');

            // Foreign key constraint'i tekrar ekle
            DB::statement('ALTER TABLE pk_battles ADD CONSTRAINT pk_battles_live_stream_id_foreign 
                          FOREIGN KEY (live_stream_id) REFERENCES agora_channels(id) ON DELETE CASCADE');

            echo "✅ pk_battles.live_stream_id foreign key constraint restored\n";

        } catch (\Exception $e) {
            echo "❌ Error restoring foreign key constraint: " . $e->getMessage() . "\n";
        }
    }
};