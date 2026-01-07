<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FixSellerVideos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'videos:fix-seller {--dry-run : Show what would be changed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix seller videos: update user_data, ensure is_sport=true, and boost trending_score';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info('Starting seller video fix...');
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        // Find all videos that have is_sport = true (seller videos)
        $sellerVideos = Video::where('is_sport', true)->get();

        $this->info("Found {$sellerVideos->count()} seller videos (is_sport=true)");

        $updated = 0;
        $errors = 0;

        foreach ($sellerVideos as $video) {
            try {
                $changes = [];

                // Get the user for this video
                $userId = $video->user_id;
                $user = User::find($userId);

                if (!$user) {
                    $this->warn("  Video {$video->id}: User {$userId} not found!");
                    $errors++;
                    continue;
                }

                // Update user_data embedding
                $userData = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username ?? $user->nickname,
                    'nickname' => $user->nickname,
                    'profile_photo_url' => $user->profile_photo_url,
                    'avatar' => $user->avatar,
                    'email' => $user->email,
                ];

                if ($video->user_data != $userData) {
                    $changes[] = 'user_data';
                }

                // Boost trending_score for seller videos
                if ($video->trending_score < 1000000) {
                    $changes[] = 'trending_score (boosted)';
                }

                if (empty($changes)) {
                    $this->line("  Video {$video->id}: No changes needed");
                    continue;
                }

                $this->info("  Video {$video->id}: Updating " . implode(', ', $changes));
                $this->line("    User: {$user->name} ({$user->nickname})");

                if (!$dryRun) {
                    $video->user_data = $userData;
                    $video->trending_score = max($video->trending_score ?? 0, 1000000);
                    $video->save();
                }

                $updated++;

            } catch (\Exception $e) {
                $this->error("  Video {$video->id}: Error - {$e->getMessage()}");
                $errors++;
            }
        }

        $this->newLine();
        $this->info("Summary:");
        $this->info("  - Total seller videos: {$sellerVideos->count()}");
        $this->info("  - Updated: {$updated}");
        $this->info("  - Errors: {$errors}");

        if ($dryRun) {
            $this->warn('DRY RUN - Run without --dry-run to apply changes');
        }

        return 0;
    }
}
