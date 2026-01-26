<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::table('live_stream_participants', function (Blueprint $table) {
            // Drop foreign key first if it exists
            // The constraint name is usually table_column_foreign
            $table->dropForeign(['live_stream_id']);
        });

        // Change the column type to string
        DB::statement('ALTER TABLE live_stream_participants ALTER COLUMN live_stream_id TYPE VARCHAR(255)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We cannot easily revert string back to int if there are non-numeric values
        // But for structure:
        DB::statement('ALTER TABLE live_stream_participants ALTER COLUMN live_stream_id TYPE BIGINT USING live_stream_id::bigint');

        Schema::table('live_stream_participants', function (Blueprint $table) {
            $table->foreign('live_stream_id')->references('id')->on('agora_channels')->onDelete('cascade');
        });
    }
};
