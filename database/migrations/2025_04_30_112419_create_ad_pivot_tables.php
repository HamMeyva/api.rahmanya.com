<?php

use App\Models\Ad\Ad;
use App\Models\Common\City;
use App\Models\Demographic\Os;
use App\Models\Relations\Team;
use App\Models\Demographic\Gender;
use App\Models\Demographic\AgeRange;
use App\Models\Demographic\Placement;
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
        /* start::Pivot Tables */
        Schema::create('ad_placement', function (Blueprint $table) {
            $table->timestampsTz();
            $table->foreignIdFor(Ad::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Placement::class)->constrained()->cascadeOnDelete();
        });
        Schema::create('ad_target_age_range', function (Blueprint $table) {
            $table->timestampsTz();
            $table->foreignIdFor(Ad::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(AgeRange::class)->constrained()->cascadeOnDelete();
        });
        Schema::create('ad_target_gender', function (Blueprint $table) {
            $table->timestampsTz();
            $table->foreignIdFor(Ad::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Gender::class)->constrained()->cascadeOnDelete();
        });
        Schema::create('ad_target_city', function (Blueprint $table) {
            $table->timestampsTz();
            $table->foreignIdFor(Ad::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(City::class)->constrained()->cascadeOnDelete();
        });
        Schema::create('ad_target_team', function (Blueprint $table) {
            $table->timestampsTz();
            $table->foreignIdFor(Ad::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Team::class)->constrained()->cascadeOnDelete();
        });
        Schema::create('ad_target_os', function (Blueprint $table) {
            $table->timestampsTz();
            $table->foreignIdFor(Ad::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Os::class)->constrained()->cascadeOnDelete();
        });
        /* end::Pivot Tables */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_target_os');
        Schema::dropIfExists('ad_target_team');
        Schema::dropIfExists('ad_target_city');
        Schema::dropIfExists('ad_target_gender');
        Schema::dropIfExists('ad_target_age_range');
        Schema::dropIfExists('ad_placement');
    }
};
