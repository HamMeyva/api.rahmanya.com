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
        Schema::create('follows', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->uuid('follower_id');
            $table->uuid('followed_id');

            // Add status column for follow requests
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('approved');

            // Add notification preferences
            $table->boolean('notify_on_accept')->default(true);



            // Foreign keys
            $table->foreign('follower_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('followed_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes for faster queries
            $table->index('follower_id');
            $table->index('followed_id');
            $table->index('status');

            // Composite indexes for common queries
            $table->index(['followed_id', 'status']);
            $table->index(['follower_id', 'status']);

            // Unique constraint to prevent duplicate follows
            $table->unique(['follower_id', 'followed_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follows');
    }
};
