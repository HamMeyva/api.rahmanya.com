<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::create('user_taggable', function (Blueprint $table) {
            // Primary Key
            $table->id();
            $table->timestampsTz();
            $table->softDeletesTz();

            // Foreign Keys (Declared Early for Clarity)
            $table->uuid('user_id');
            $table->uuid('taggable_id'); // Consider renaming to `tagged_user_id` if not polymorphic

            // Core Relationship Constraints
            $table->unique(['user_id', 'taggable_id']); // Prevent duplicate tagging

            // Status & Preferences
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->boolean('notify_on_tag')->default(true);
            $table->enum('visibility', ['public', 'followers', 'mutual_followers', 'private'])->default('public');

            // Foreign Key Constraints (Grouped Together)
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('taggable_id')
                ->references('id')
                ->on('users') // If polymorphic, use ->on('taggables')
                ->onDelete('cascade');

            // Indexes (Optimized for Common Queries)
            $table->index('status');
            $table->index('visibility');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_taggable');
    }
};
