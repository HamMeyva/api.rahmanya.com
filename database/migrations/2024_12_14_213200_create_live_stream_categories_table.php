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
        Schema::create('live_stream_categories', function (Blueprint $table) {
            $table->id();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);


            $table->foreign('parent_id')
                  ->references('id')
                  ->on('live_stream_categories')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_stream_categories');
    }
};
