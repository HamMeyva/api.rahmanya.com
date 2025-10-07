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
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->char('iso2', 2)->nullable();
            $table->char('iso3', 3)->nullable();
            $table->char('numeric_code', 3)->nullable();
            $table->string('phone_code', 255)->nullable();
            $table->string('capital', 255)->nullable();
            $table->string('currency', 255)->nullable();
            $table->string('currency_symbol', 255)->nullable();
            $table->string('tld', 255)->nullable();
            $table->string('native', 255)->nullable();
            $table->string('region', 255)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
        });
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->string('code', 6)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
        });
        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId("city_id")->constrained()->cascadeOnDelete();
            $table->string("name", 255);
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
