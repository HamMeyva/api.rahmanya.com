<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pk_battles', function (Blueprint $table) {
            // Devre sistemi için alanlar
            $table->integer('total_rounds')->default(1); // 1, 3, 5, 7 devre
            $table->integer('current_round')->default(1);
            $table->integer('round_duration_minutes')->default(5); // 5, 10, 15 dakika
            $table->timestamp('round_started_at')->nullable();
            $table->timestamp('round_ends_at')->nullable();
            
            // Skorlar - her devre için ayrı tutulacak
            $table->json('round_scores')->nullable(); // [{"round": 1, "challenger": 150, "opponent": 200}, ...]
            $table->json('battle_settings')->nullable(); // Devre sayısı, süre vs.
            
            // Battle'ın genel durumu
            $table->boolean('is_round_active')->default(false);
            $table->integer('challenger_goals')->default(0); // Kazanılan devre sayısı
            $table->integer('opponent_goals')->default(0); // Kazanılan devre sayısı
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pk_battles', function (Blueprint $table) {
            $table->dropColumn([
                'total_rounds',
                'current_round', 
                'round_duration_minutes',
                'round_started_at',
                'round_ends_at',
                'round_scores',
                'battle_settings',
                'is_round_active',
                'challenger_goals',
                'opponent_goals'
            ]);
        });
    }
};
