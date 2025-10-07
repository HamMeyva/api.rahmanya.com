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
        Schema::table('user_team', function (Blueprint $table) {
            // Add index for team_id to optimize team-related user queries
            // This will optimize query #7: select "id" from ((select "id" from "users" where "primary_team_id" in...
            if (!Schema::hasIndex('user_team', 'user_team_team_id_index')) {
                $table->index('team_id', 'user_team_team_id_index');
            }

            // Add index for user_id to optimize user-related team queries
            if (!Schema::hasIndex('user_team', 'user_team_user_id_index')) {
                $table->index('user_id', 'user_team_user_id_index');
            }

            // Add composite index for both team_id and user_id
            if (!Schema::hasIndex('user_team', 'user_team_team_id_user_id_index')) {
                $table->index(['team_id', 'user_id'], 'user_team_team_id_user_id_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_team', function (Blueprint $table) {
            // Drop indexes if they exist
            $table->dropIndexIfExists('user_team_team_id_index');
            $table->dropIndexIfExists('user_team_user_id_index');
            $table->dropIndexIfExists('user_team_team_id_user_id_index');
        });
    }
};
