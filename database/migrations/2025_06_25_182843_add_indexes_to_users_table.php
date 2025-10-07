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
        Schema::table('users', function (Blueprint $table) {
            // Add index for is_frozen and is_banned columns to optimize user filtering queries
            // This will optimize query #8: select * from "users" where "is_frozen" = $1 and "is_banned" = $2...
            if (!Schema::hasIndex('users', 'users_is_frozen_is_banned_index')) {
                $table->index(['is_frozen', 'is_banned'], 'users_is_frozen_is_banned_index');
            }

            // Add index for primary_team_id to optimize team-related user queries
            // This will optimize query #7: select "id" from ((select "id" from "users" where "primary_team_id" in ($1)...
            if (!Schema::hasIndex('users', 'users_primary_team_id_index')) {
                $table->index('primary_team_id', 'users_primary_team_id_index');
            }

            // Add index for deleted_at to optimize soft delete filtering
            // This will optimize queries #2, #8, #12: select * from "users" where "users"."deleted_at" is null...
            if (!Schema::hasIndex('users', 'users_deleted_at_index')) {
                $table->index('deleted_at', 'users_deleted_at_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop indexes if they exist
            $table->dropIndexIfExists('users_is_frozen_is_banned_index');
            $table->dropIndexIfExists('users_primary_team_id_index');
            $table->dropIndexIfExists('users_deleted_at_index');
        });
    }
};
