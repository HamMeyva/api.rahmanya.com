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
        Schema::create('contact_forms', function (Blueprint $table) {
            $table->id();
            $table->timestampsTz();

            $table->uuid('user_id')->nullable(); // UUID olarak değiştirildi
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            $table->string('full_name', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->text('message')->nullable();

            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();

            $table->uuid('editor_id')->nullable(); // UUID olarak değiştirildi
            $table->foreign('editor_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_forms');
    }
};
