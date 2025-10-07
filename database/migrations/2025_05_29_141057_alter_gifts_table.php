<?php

use App\Models\Relations\Team;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('gifts', function (Blueprint $table) {
            $table->dropColumn([
                'image_path',
                'video_path',
            ]);
            $table->boolean('has_variants')->default(false);
            $table->dropForeign(['team_id']);
            $table->dropColumn('team_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gifts', function (Blueprint $table) {
            //
        });
    }
};
