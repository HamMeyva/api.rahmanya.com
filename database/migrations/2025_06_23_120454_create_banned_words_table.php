<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::create('banned_words', function (Blueprint $table) {
            $table->id();
            $table->timestampsTz();
            $table->string('word')->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banned_words');
    }
};
