<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Relations\Team;

return new class extends Migration
{
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gifts', function (Blueprint $table) {
            $table->id();
            $table->timestampsTz();
            $table->boolean('is_active')->default(true);
            $table->string('image_path')->nullable();
            $table->string('video_path')->nullable();
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->integer('price')->default(0);
            $table->boolean('is_discount')->default(false);
            $table->integer('discounted_price')->nullable();
            $table->boolean('is_custom_gift')->default(false);
            $table->foreignIdFor(Team::class)->nullable()->constrained()->nullOnDelete();
            $table->integer('queue')->default(0);
            $table->integer('total_usage')->default(0);
            $table->integer('total_sales')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gifts');
    }
};
