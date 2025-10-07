<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public $withinTransaction = false;

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // email alanının unique özelliğini kaldır
            $table->dropUnique(['email']);

            // parent_user_id ekle (aynı email adresine bağlı hesapların ana hesabını belirtmek için)
            $table->uuid('parent_user_id')->nullable()->after('id');
            $table->foreign('parent_user_id')->references('id')->on('users')->nullOnDelete();

            // Hesap türü (ana hesap veya ikincil hesap)
            $table->string('account_type')->default('primary')->after('parent_user_id'); // primary, secondary
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['parent_user_id']);
            $table->dropColumn(['parent_user_id', 'account_type']);
            $table->string('email', 255)->unique()->change();
        });
    }
};
