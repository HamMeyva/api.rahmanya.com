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
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key', 255)->primary(); // Performans için 255 karakter sınırlandırıldı
            $table->text('value'); // PostgreSQL'de mediumText yerine text kullanıldı
            $table->bigInteger('expiration'); // Unix timestamp olarak saklanacaksa
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key', 255)->primary(); // String anahtar için sınır kondu
            $table->string('owner', 255); // Owner kimliği varsa string olarak kısıtlandı
            $table->bigInteger('expiration'); // Unix timestamp olarak kullanılacaksa
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
