<?php

use App\Models\Ad\Ad;
use App\Models\Ad\Advertiser;
use App\Models\Common\City;
use App\Models\Common\Country;
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
        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreignIdFor(Advertiser::class)->nullable()->constrained()->nullOnDelete();

            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('redirect_url')->nullable();
            $table->smallInteger('media_type_id')->nullable();
            $table->string('media_path')->nullable();
            $table->smallInteger('status_id');
            $table->smallInteger('payment_status_id');
            $table->dateTimeTz('paid_at')->nullable();
            $table->date('start_date')->nullable();
            $table->time('show_start_time')->nullable();
            $table->time('show_end_time')->nullable();
            $table->decimal('total_budget', 11, 2)->default(0);
            $table->integer('total_hours')->default(0);
            $table->decimal('bid_amount', 11, 2)->default(0);

            $table->foreignId('target_country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->foreignId('target_language_id')->nullable()->constrained('languages')->nullOnDelete();


            $table->bigInteger('impressions')->default(0);
            $table->bigInteger('clicks')->default(0);
            $table->decimal('ctr', 5, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
};
