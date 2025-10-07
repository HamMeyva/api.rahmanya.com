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
            $table->string('notification_type')->nullable();
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->string('url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('notification_type');
            $table->dropColumn('title');
            $table->dropColumn('body');
            $table->dropColumn('url');
        });
    }
};
