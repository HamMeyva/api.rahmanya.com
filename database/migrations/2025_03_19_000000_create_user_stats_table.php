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
        Schema::create('user_stats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->softDeletesTz();
            $table->timestampsTz();
            $table->uuid('user_id')->unique();
            $table->unsignedInteger('follower_count')->default(0);
            $table->unsignedInteger('following_count')->default(0);
            $table->unsignedInteger('video_count')->default(0);
            $table->unsignedInteger('total_views')->default(0);
            $table->unsignedInteger('total_likes')->default(0);
            $table->unsignedInteger('total_comments')->default(0);


            // Foreign key
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Index
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_stats');
    }
};
