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
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->timestampsTz();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->boolean('is_banned')->default(false);
            $table->timestampTz('banned_at')->nullable();
            $table->string('device_type', 50)->nullable();
            $table->string('device_unique_id', 255)->nullable();
            $table->string('device_os', 50)->nullable();
            $table->string('device_os_version', 50)->nullable();
            $table->string('device_model', 100)->nullable();
            $table->string('device_brand', 100)->nullable();
            $table->string('device_ip', 45)->nullable();
            $table->text('token')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
