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
        Schema::table('teams', function (Blueprint $table) {
            // Add index for team name to optimize name-based lookups
            if (!Schema::hasIndex('teams', 'teams_name_index')) {
                $table->index('name', 'teams_name_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            // Drop indexes if they exist
            $table->dropIndexIfExists('teams_name_index');
        });
    }
};
