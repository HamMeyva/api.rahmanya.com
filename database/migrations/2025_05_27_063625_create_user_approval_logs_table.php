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
        Schema::create('user_approval_logs', function (Blueprint $table) {
            $table->id();
            $table->timestampsTz();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->boolean('approved');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_approval_logs');
    }
};
