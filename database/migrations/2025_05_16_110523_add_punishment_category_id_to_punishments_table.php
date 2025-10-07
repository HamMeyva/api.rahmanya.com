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
            $table->foreignId('punishment_category_id')->nullable()->constrained('punishment_categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('punishments', function (Blueprint $table) {
            $table->dropForeign(['punishment_category_id']);
            $table->dropColumn('punishment_category_id');
        });
    }
};
