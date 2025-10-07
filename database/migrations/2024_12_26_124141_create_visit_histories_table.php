<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('visit_histories', function (Blueprint $table) {
            $table->id();
            $table->timestampsTz();

            $table->uuid('user_id')->nullable(); // UUID olarak değiştirildi
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->morphs('action'); // action_id ve action_type sütunlarını otomatik ekler
            $table->text('action_taken')->nullable();
            $table->boolean('is_read')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visit_histories');
    }
};
