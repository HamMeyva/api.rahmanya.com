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
        Schema::table('live_stream_categories', function (Blueprint $table) {
            // Add index for is_active to optimize active category filtering and counting
            if (!Schema::hasIndex('live_stream_categories', 'live_stream_categories_is_active_index')) {
                $table->index('is_active', 'live_stream_categories_is_active_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('live_stream_categories', function (Blueprint $table) {
            // Drop index if it exists
            $table->dropIndexIfExists('live_stream_categories_is_active_index');
        });
    }
};
