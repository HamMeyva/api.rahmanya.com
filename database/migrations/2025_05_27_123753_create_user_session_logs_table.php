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
        Schema::create('user_session_logs', function (Blueprint $table) {
            $table->id();
            $table->timestampsTz();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->timestampTz('start_at');
            $table->timestampTz('end_at')->nullable();
            $table->integer('duration')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_session_logs');
    }
};
