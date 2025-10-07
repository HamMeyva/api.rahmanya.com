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
        Schema::create('musics', function (Blueprint $table) {
            $table->id();
            $table->timestampsTz();
            $table->string('title');
            $table->string('slug')->unique();
            $table->foreignId('artist_id')->nullable()->constrained('artists')->cascadeOnDelete();
            $table->foreignId('music_category_id')->nullable()->constrained('music_categories')->cascadeOnDelete();
            $table->string('music_path')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('musics');
    }
};
