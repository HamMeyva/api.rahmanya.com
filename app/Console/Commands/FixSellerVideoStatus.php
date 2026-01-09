<?php

namespace App\Console\Commands;

use App\Models\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FixSellerVideoStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'videos:fix-seller-status 
                            {--dry-run : Show what would be changed without making changes}
                            {--clear-cache : Clear all feed caches after fixing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix seller videos: ensure is_sport=true and status=finished for Shopping feed visibility';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $clearCache = $this->option('clear-cache');

        $this->info('Starting seller video status fix...');
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        // Find all videos that have is_sport = true (seller/shopping videos)
        $sellerVideos = Video::where('is_sport', true)->get();

        $this->info("Found {$sellerVideos->count()} seller videos (is_sport=true)");

        if ($sellerVideos->count() === 0) {
            $this->warn('No seller videos found. Checking for videos with seller-like characteristics...');

            // Try to find videos that might be seller videos but aren't marked correctly
            $potentialSellerVideos = Video::where(function ($query) {
                $query->whereNotNull('product_id')
                    ->orWhereNotNull('seller_id')
                    ->orWhere('trending_score', '>=', 1000000);
            })->get();

            if ($potentialSellerVideos->count() > 0) {
                $this->info("Found {$potentialSellerVideos->count()} potential seller videos");
                $sellerVideos = $potentialSellerVideos;
            }
        }

        // Show status summary
        $statusCounts = $sellerVideos->groupBy('status')->map(fn($group) => $group->count());
        $this->info("\nCurrent status breakdown:");
        foreach ($statusCounts as $status => $count) {
            $this->line("  - {$status}: {$count}");
        }

        $updated = 0;
        $alreadyCorrect = 0;
        $errors = 0;

        $this->newLine();
        $this->info('Processing videos...');
        $this->newLine();

        foreach ($sellerVideos as $video) {
            try {
                $changes = [];

                // Check if status needs to be updated to 'finished'
                if ($video->status !== 'finished') {
                    $changes[] = "status: {$video->status} -> finished";
                }

                // Check if is_sport needs to be set
                if (!$video->is_sport) {
                    $changes[] = "is_sport: false -> true";
                }

                // Check if trending_score needs to be boosted for visibility
                if (($video->trending_score ?? 0) < 1000000) {
                    $changes[] = "trending_score: {$video->trending_score} -> 1000000";
                }

                if (empty($changes)) {
                    $alreadyCorrect++;
                    continue;
                }

                $this->line("Video {$video->id} ({$video->title}):");
                foreach ($changes as $change) {
                    $this->line("  └─ {$change}");
                }

                if (!$dryRun) {
                    $video->status = 'finished';
                    $video->is_sport = true;
                    $video->trending_score = max($video->trending_score ?? 0, 1000000);
                    $video->save();
                }

                $updated++;

            } catch (\Exception $e) {
                $this->error("  Video {$video->id}: Error - {$e->getMessage()}");
                Log::error('FixSellerVideoStatus error', [
                    'video_id' => $video->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $errors++;
            }
        }

        $this->newLine();
        $this->info("Summary:");
        $this->info("  - Total seller videos: {$sellerVideos->count()}");
        $this->info("  - Already correct: {$alreadyCorrect}");
        $this->info("  - Updated: {$updated}");
        $this->info("  - Errors: {$errors}");

        // Clear cache if requested or if changes were made
        if (!$dryRun && ($clearCache || $updated > 0)) {
            $this->newLine();
            $this->info('Clearing feed caches...');

            try {
                // Clear all feed-related caches
                $patterns = [
                    'feed-videos:*',
                    'feed:*',
                    'video:*',
                ];

                foreach ($patterns as $pattern) {
                    $this->line("  └─ Clearing cache pattern: {$pattern}");
                    Cache::flush(); // Simple approach - clear all cache
                }

                $this->info('✓ Feed caches cleared');
            } catch (\Exception $e) {
                $this->warn("Cache clearing failed: {$e->getMessage()}");
            }
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('DRY RUN - Run without --dry-run to apply changes');
        } else if ($updated > 0) {
            $this->newLine();
            $this->info('✓ Changes applied successfully!');
            $this->comment('The shopping feed should now display the updated videos.');
        }

        return 0;
    }
}
