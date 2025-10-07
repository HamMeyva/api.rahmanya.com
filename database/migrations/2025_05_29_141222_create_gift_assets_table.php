<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::create('gift_assets', function (Blueprint $table) {
            $table->id();
            $table->timestampsTz();
            $table->foreignId('gift_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('image_path')->nullable();
            $table->string('video_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_assets');
    }
};
