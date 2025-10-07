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
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->timestampsTz();

            $table->string('cover_image', 255)->nullable();
            $table->string('title', 255)->nullable();
            $table->string('slug', 255)->nullable()->unique();
            $table->string('short_body', 500)->nullable();
            $table->text('long_body')->nullable();

            $table->boolean('is_published')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->boolean('menu_show')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
