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
        Schema::table('agora_stream_statistics', function (Blueprint $table) {
            $table->dropColumn('agora_channel_id');
            $table->string('agora_channel_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       //
    }
};
