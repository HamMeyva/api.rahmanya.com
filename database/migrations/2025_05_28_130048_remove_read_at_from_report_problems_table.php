<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::table('report_problems', function (Blueprint $table) {
            $table->dropColumn('read_at');
        });
    }

    public function down(): void
    {
        Schema::table('report_problems', function (Blueprint $table) {
            //
        });
    }
};
