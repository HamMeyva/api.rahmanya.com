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
        Schema::create('popular_searches', function (Blueprint $table) {
            $table->id();
            $table->timestampsTz();
            $table->string('title')->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(false);
            $table->integer('queue')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('popular_searches');
    }
};
