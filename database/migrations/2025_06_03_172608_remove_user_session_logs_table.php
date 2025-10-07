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
        Schema::dropIfExists('user_session_logs');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
