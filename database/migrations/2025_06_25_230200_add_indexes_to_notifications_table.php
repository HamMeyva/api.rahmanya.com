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
        Schema::table('notifications', function (Blueprint $table) {
            // Add composite index for notifiable_id and notifiable_type to optimize notification lookups
            if (!Schema::hasIndex('notifications', 'notifications_notifiable_id_notifiable_type_index')) {
                $table->index(['notifiable_id', 'notifiable_type'], 'notifications_notifiable_id_notifiable_type_index');
            }

            // Add index for read_at to optimize unread notification filtering
            if (!Schema::hasIndex('notifications', 'notifications_read_at_index')) {
                $table->index('read_at', 'notifications_read_at_index');
            }

            // Add index for created_at to optimize ordering by creation date
            if (!Schema::hasIndex('notifications', 'notifications_created_at_index')) {
                $table->index('created_at', 'notifications_created_at_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Drop indexes if they exist
            $table->dropIndexIfExists('notifications_notifiable_id_notifiable_type_index');
            $table->dropIndexIfExists('notifications_read_at_index');
            $table->dropIndexIfExists('notifications_created_at_index');
        });
    }
};
