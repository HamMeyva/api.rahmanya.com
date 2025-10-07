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
        Schema::table('failed_jobs', function (Blueprint $table) {
            // Add index for uuid to optimize failed job lookups
            if (!Schema::hasIndex('failed_jobs', 'failed_jobs_uuid_index')) {
                $table->index('uuid', 'failed_jobs_uuid_index');
            }

            // Add index for queue to optimize queue-specific failed job lookups
            if (!Schema::hasIndex('failed_jobs', 'failed_jobs_queue_index')) {
                $table->index('queue', 'failed_jobs_queue_index');
            }

            // Add index for failed_at to optimize chronological lookups
            if (!Schema::hasIndex('failed_jobs', 'failed_jobs_failed_at_index')) {
                $table->index('failed_at', 'failed_jobs_failed_at_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('failed_jobs', function (Blueprint $table) {
            // Drop indexes if they exist
            $table->dropIndexIfExists('failed_jobs_uuid_index');
            $table->dropIndexIfExists('failed_jobs_queue_index');
            $table->dropIndexIfExists('failed_jobs_failed_at_index');
        });
    }
};
