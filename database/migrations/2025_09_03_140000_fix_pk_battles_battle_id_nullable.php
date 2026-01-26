<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public $withinTransaction = false;

    public function up(): void
    {
        try {
            echo "🔧 Making pk_battles.battle_id nullable...\n";

            // battle_id alanını nullable yap
            DB::statement('ALTER TABLE pk_battles ALTER COLUMN battle_id DROP NOT NULL');

            echo "✅ pk_battles.battle_id is now nullable\n";

        } catch (\Exception $e) {
            echo "❌ Error making battle_id nullable: " . $e->getMessage() . "\n";
            // Bu alanın zaten nullable olması durumunda hata vermemesi için
            if (strpos($e->getMessage(), 'column "battle_id" of relation "pk_battles" does not exist') === false) {
                throw $e;
            }
        }
    }

    public function down(): void
    {
        try {
            // battle_id alanını tekrar NOT NULL yap
            DB::statement('ALTER TABLE pk_battles ALTER COLUMN battle_id SET NOT NULL');
            echo "✅ pk_battles.battle_id is now NOT NULL again\n";
        } catch (\Exception $e) {
            echo "❌ Error reverting battle_id: " . $e->getMessage() . "\n";
        }
    }
};