<?php

use App\Models\Demographic\Gender;
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
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->uuid('collection_uuid')->nullable()->index();
            $table->string('avatar', 255)->nullable();
            $table->string('name', 255)->nullable();
            $table->string('surname', 255)->nullable();
            $table->string('nickname', 255)->unique();
            $table->string('email', 255)->unique();
            $table->string('phone', 20)->nullable();

            $table->integer('coin_balance')->default(0);
            $table->integer('earned_coin_balance')->default(0);


            $table->foreignIdFor(Gender::class)->nullable()->constrained()->nullOnDelete();
            $table->date('birthday')->nullable();
            $table->text('bio')->nullable();
            $table->text('slogan')->nullable();
            $table->foreignId('preferred_language_id')->nullable()->constrained('languages')->nullOnDelete();
            $table->foreignId('primary_team_id')->nullable()->constrained('teams')->nullOnDelete();
      
            $table->bigInteger('agora_uid')->nullable()->unique();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('fcm_token', 255)->nullable();

            $table->boolean('is_frozen')->default(0);
            $table->boolean('is_private')->default(0);
            $table->boolean('is_banned')->default(false);
            $table->timestampTz('banned_at')->nullable();
            $table->string('ban_reason')->nullable();

            // Privacy settings as JSON
            $table->json('privacy_settings')->nullable()->default(json_encode([
                'profile_visibility' => 'public',
                'show_following' => true,
                'show_followers' => true,
                'allow_tagging' => true,
                'allow_comments' => true,
                'comment_privacy' => 'everyone',
                'tag_privacy' => 'everyone'
            ]));

            // Notification preferences
            $table->boolean('general_email_notification')->default(true); //paneldeki toplu bildirimler için
            $table->boolean('general_sms_notification')->default(true); //paneldeki toplu bildirimler için
            $table->boolean('general_push_notification')->default(true); //paneldeki toplu bildirimler için
            
            $table->boolean('like_notification')->default(true);
            $table->boolean('comment_notification')->default(true);
            $table->boolean('follower_notification')->default(true);
            $table->boolean('taggable_notification')->default(true);

            // Legacy taggable setting - consider migrating to privacy_settings
            $table->smallInteger('taggable')->default(0)->comment('0: Everyone, 1: Followings, 2: Mutual Followers, 3: Nobody');

            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('password', 255);
            $table->rememberToken();


            $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->integer('age')->nullable(); //her gece  1 kere çalışan bir job ile yaşlar güncellenir
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email', 255)->primary();
            $table->string('token', 255);
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {

            $table->string('id')->primary();

            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('ip_address', 45)->nullable();

            $table->text('user_agent')->nullable();

            $table->text('payload');

            $table->integer('last_activity')->index();
        });

        Schema::create('otp_verifications', function (Blueprint $table) {
            $table->id();
            $table->timestampsTz();
            $table->string('phone', 20)->unique();
            $table->string('otp', 10);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('otp_verifications');
    }
};
