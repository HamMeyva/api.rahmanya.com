<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

class CreateRelatedStreamsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Migration is intentionally left empty
        // Table is created manually via SQL script
        // This prevents PostgreSQL transaction issues
        Log::info('Related streams table migration skipped - table should be created manually');
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // Migration is intentionally left empty
        // Table should be dropped manually if needed
        Log::info('Related streams table rollback skipped - table should be dropped manually');
    }
}