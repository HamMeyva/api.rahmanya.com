<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up()
    {
        if (!Schema::hasColumn('agora_channels', 'zego_room_id')) {
            Schema::table('agora_channels', function (Blueprint $table) {
                $table->string('zego_room_id')->nullable();
                $table->json('zego_config')->nullable();
                $table->timestamp('zego_created_at')->nullable();
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('agora_channels', 'zego_room_id')) {
            Schema::table('agora_channels', function (Blueprint $table) {
                $table->dropColumn(['zego_room_id', 'zego_config', 'zego_created_at']);
            });
        }
    }
};
