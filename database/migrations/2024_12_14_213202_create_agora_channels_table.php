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
        Schema::create('agora_channels', function (Blueprint $table) {
            $table->id();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->uuid('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('channel_name', 255)->nullable()->index();

            $table->foreignId('language_id')->nullable()->constrained('languages')->nullOnDelete();
            $table->boolean('is_online')->default(false); 

            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->string('stream_key')->nullable();
            $table->string('rtmp_url')->nullable();
            $table->string('playback_url')->nullable();
            $table->integer('viewer_count')->default(0);
            $table->integer('max_viewer_count')->default(0);
            $table->integer('total_likes')->default(0);
            $table->integer('total_gifts')->default(0);
            $table->integer('total_coins_earned')->default(0);
            $table->json('tags')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('live_stream_categories')->nullOnDelete();

            $table->boolean('is_featured')->default(false);
            $table->json('settings')->nullable();
            $table->smallInteger('status_id')->default(1);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agora_channels');
    }
};
