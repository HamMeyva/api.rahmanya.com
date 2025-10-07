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
        Schema::table('sessions', function (Blueprint $table) {
            // Add index for user_id to speed up user-related session queries
            if (!Schema::hasIndex('sessions', 'sessions_user_id_index')) {
                $table->index('user_id', 'sessions_user_id_index');
            }

            // Add index for last_activity to speed up session garbage collection
            if (!Schema::hasIndex('sessions', 'sessions_last_activity_index')) {
                $table->index('last_activity', 'sessions_last_activity_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            // Drop indexes if they exist
            $table->dropIndexIfExists('sessions_user_id_index');
            $table->dropIndexIfExists('sessions_last_activity_index');
        });
    }
};
