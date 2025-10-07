<?php

namespace App\Jobs\Feed;

use App\Models\User;
use Illuminate\Bus\Queueable;
use App\Services\Video\FeedService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Throwable;

class UpdateAllUserFeedsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct() {}

    public function handle(FeedService $feedService): void
    {
        Log::info('Starting UpdateAllUserFeedsJob');

        $processedCount = 0;

        User::query()
            ->whereRaw("is_frozen = false")
            ->whereRaw("is_banned = false")
            ->chunk(800, function ($users) use ($feedService, &$processedCount) {
                foreach ($users as $user) {
                    try {
                        $feedService->updateFeedCache($user, 'mixed');
                        $feedService->updateFeedCache($user, 'following');
                        $feedService->updateFeedCache($user, 'sport');
                        $processedCount++;
                    } catch (Throwable $e) {
                        Log::error("Failed to update feed cache for user {$user->id}: {$e->getMessage()}");
                    }
                }
                Log::info("Processed {$processedCount} users so far...");
            });

        Log::info("Finished UpdateAllUserFeedsJob. Total users processed: {$processedCount}");
    }
}
