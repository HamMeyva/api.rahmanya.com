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
        Schema::create('user_device_logins', function (Blueprint $table) {
            $table->id();
            $table->timestampsTz();
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('device_type', 50)->nullable();
            $table->string('device_unique_id', 255);
            $table->string('device_os', 50)->nullable();
            $table->string('device_os_version', 50)->nullable();
            $table->string('device_model', 100)->nullable();
            $table->string('device_brand', 100)->nullable();
            $table->string('device_ip', 45)->nullable();
            $table->text('access_token')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->index(['user_id', 'device_unique_id']);
            $table->index('last_activity_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_device_logins');
    }
};
