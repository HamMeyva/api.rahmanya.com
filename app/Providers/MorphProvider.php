<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Ad\Ad;
use App\Models\Admin;
use App\Models\Story;
use App\Models\Video;
use App\Models\Coin\CoinPackage;
use App\Models\Agora\AgoraChannel;
use App\Models\Morph\ReportProblem;
use App\Models\Morph\Payment;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

class MorphProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Relation::enforceMorphMap([
            "Admin" => Admin::class,
            "User" => User::class,
            "Video" => Video::class,
            "ReportProblem" => ReportProblem::class,
            "CoinPackage" => CoinPackage::class,
            "Ad" => Ad::class,
            "Story" => Story::class,
            "AgoraChannel" => AgoraChannel::class,
            "Payment" => Payment::class,
        ]);
    }
}
