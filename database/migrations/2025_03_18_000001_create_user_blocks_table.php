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
        Schema::create('user_blocks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->uuid('blocker_id');
            $table->uuid('blocked_id');
            $table->text('reason')->nullable();

            // Foreign keys
            $table->foreign('blocker_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('blocked_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes for faster queries
            $table->index('blocker_id');
            $table->index('blocked_id');

            // Unique constraint to prevent duplicate blocks
            $table->unique(['blocker_id', 'blocked_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_blocks');
    }
};
