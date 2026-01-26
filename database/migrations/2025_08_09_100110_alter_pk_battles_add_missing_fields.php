<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        if (Schema::hasTable('pk_battles')) {
            Schema::table('pk_battles', function (Blueprint $table) {
                if (!Schema::hasColumn('pk_battles', 'battle_id')) {
                    $table->string('battle_id')->unique()->after('id');
                }
                if (!Schema::hasColumn('pk_battles', 'opponent_stream_id')) {
                    $table->string('opponent_stream_id')->nullable()->after('opponent_id');
                }
                if (!Schema::hasColumn('pk_battles', 'duration_seconds')) {
                    $table->integer('duration_seconds')->default(300)->after('opponent_stream_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pk_battles')) {
            Schema::table('pk_battles', function (Blueprint $table) {
                if (Schema::hasColumn('pk_battles', 'battle_id')) {
                    $table->dropUnique(['battle_id']);
                    $table->dropColumn('battle_id');
                }
                if (Schema::hasColumn('pk_battles', 'opponent_stream_id')) {
                    $table->dropColumn('opponent_stream_id');
                }
                if (Schema::hasColumn('pk_battles', 'duration_seconds')) {
                    $table->dropColumn('duration_seconds');
                }
            });
        }
    }
};


