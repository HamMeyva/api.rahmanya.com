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
    protected $description = 'Fix seller videos: create Shadow Seller Users, update user_data, and boost trending_score';

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
        $usersCreated = 0;
        $errors = 0;

        foreach ($sellerVideos as $video) {
            try {
                $changes = [];
                $originalUserId = $video->user_id;

                // Try to find the user
                $user = User::find($originalUserId);

                if (!$user) {
                    // User doesn't exist - we need to create a Shadow Seller User
                    // Use the original user_id as the seller_id to create a unique nickname
                    $sellerId = $originalUserId;
                    $nickname = 'seller_' . substr($sellerId, 0, 8); // Use first 8 chars of UUID

                    // Check if a shadow user with this nickname already exists
                    $user = User::where('nickname', $nickname)->first();

                    if (!$user) {
                        // Get seller name from video's user_data if available
                        $sellerName = 'Rahmanya Seller';
                        if (!empty($video->user_data)) {
                            $userData = is_array($video->user_data) ? $video->user_data : (array) $video->user_data;
                            if (!empty($userData['name'])) {
                                $sellerName = $userData['name'];
                            } elseif (!empty($userData['username'])) {
                                $sellerName = $userData['username'];
                            }
                        }

                        $this->line("  Creating Shadow User for seller: {$sellerId}");
                        $this->line("    Nickname: {$nickname}");
                        $this->line("    Name: {$sellerName}");

                        if (!$dryRun) {
                            $user = User::create([
                                'name' => $sellerName,
                                'nickname' => $nickname,
                                'email' => $nickname . '@seller.rahmanya.com',
                                'password' => bcrypt(\Illuminate\Support\Str::random(16)),
                                'is_approved' => true,
                            ]);

                            $this->info("    ✓ Created user ID: {$user->id}");
                        }

                        $usersCreated++;
                        $changes[] = 'created_shadow_user';
                    } else {
                        $this->line("  Found existing Shadow User: {$user->nickname} (ID: {$user->id})");
                    }

                    // Update video's user_id to point to the shadow user
                    if ($user && $video->user_id !== $user->id) {
                        $changes[] = 'user_id';
                        $this->line("    Updating user_id: {$video->user_id} -> {$user->id}");

                        if (!$dryRun) {
                            $video->user_id = $user->id;
                        }
                    }
                }

                if ($user) {
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
                    if (($video->trending_score ?? 0) < 1000000) {
                        $changes[] = 'trending_score';
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
                } else {
                    $this->warn("  Video {$video->id}: Could not create/find user (dry-run mode)");
                }

            } catch (\Exception $e) {
                $this->error("  Video {$video->id}: Error - {$e->getMessage()}");
                Log::error('FixSellerVideos error', [
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
        $this->info("  - Shadow users created: {$usersCreated}");
        $this->info("  - Videos updated: {$updated}");
        $this->info("  - Errors: {$errors}");

        if ($dryRun) {
            $this->warn('DRY RUN - Run without --dry-run to apply changes');
        } else {
            $this->info('✓ Changes applied successfully!');
            $this->newLine();
            $this->comment('Note: You may need to clear the feed cache for changes to appear:');
            $this->comment('  php artisan cache:clear');
        }

        return 0;
    }
}
