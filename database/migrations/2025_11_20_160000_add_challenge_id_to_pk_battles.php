<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ✅ Disable transactions for PostgreSQL compatibility
     */
    public $withinTransaction = false;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pk_battles', function (Blueprint $table) {
            // Add challenge_id to link PK Battle with Challenge record
            $table->string('challenge_id')->nullable()->after('id');
            $table->index('challenge_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pk_battles', function (Blueprint $table) {
            $table->dropIndex(['challenge_id']);
            $table->dropColumn('challenge_id');
        });
    }
};
