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
        Schema::table('punishments', function (Blueprint $table) {
            $table->boolean('is_direct_punishment')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('punishments', function (Blueprint $table) {
            $table->dropColumn('is_direct_punishment');
        });
    }
};
