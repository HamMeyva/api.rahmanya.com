<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public $withinTransaction = false;

    /**
     * Enhance pk_battles table for v2.0 multi-round system
     * Adds shoots_per_goal and other v2.0 specific fields
     */
    public function up(): void
    {
        Schema::table('pk_battles', function (Blueprint $table) {
            // Check if columns exist before adding
            if (!Schema::hasColumn('pk_battles', 'shoots_per_goal')) {
                $table->integer('shoots_per_goal')->default(10);
            }

            if (!Schema::hasColumn('pk_battles', 'goals_to_win')) {
                $table->integer('goals_to_win')->nullable();
            }

            if (!Schema::hasColumn('pk_battles', 'countdown_seconds')) {
                $table->integer('countdown_seconds')->default(5);
            }

            if (!Schema::hasColumn('pk_battles', 'duration_seconds')) {
                $table->integer('duration_seconds')->default(180);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pk_battles', function (Blueprint $table) {
            $columns = ['shoots_per_goal', 'goals_to_win', 'countdown_seconds', 'duration_seconds'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('pk_battles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
