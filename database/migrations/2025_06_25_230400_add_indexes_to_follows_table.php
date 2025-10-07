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
        Schema::table('follows', function (Blueprint $table) {
            // Add index for follower_id to optimize follower-based queries
            if (!Schema::hasIndex('follows', 'follows_follower_id_index')) {
                $table->index('follower_id', 'follows_follower_id_index');
            }

            // Add index for followed_id to optimize followed-based queries
            if (!Schema::hasIndex('follows', 'follows_followed_id_index')) {
                $table->index('followed_id', 'follows_followed_id_index');
            }

            // Add composite index for both follower_id and followed_id
            if (!Schema::hasIndex('follows', 'follows_follower_id_followed_id_index')) {
                $table->index(['follower_id', 'followed_id'], 'follows_follower_id_followed_id_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('follows', function (Blueprint $table) {
            // Drop indexes if they exist
            $table->dropIndexIfExists('follows_follower_id_index');
            $table->dropIndexIfExists('follows_followed_id_index');
            $table->dropIndexIfExists('follows_follower_id_followed_id_index');
        });
    }
};
