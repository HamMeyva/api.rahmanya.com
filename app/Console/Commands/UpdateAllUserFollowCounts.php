<?php

namespace App\Console\Commands;

use App\Models\Follow;
use App\Models\User;
use App\Models\UserStats;
use Illuminate\Console\Command;

class UpdateAllUserFollowCounts extends Command
{
    protected $signature = 'app:update-all-user-follow-counts';
    protected $description = 'Update follower and following counts for all users in the user_stats MongoDB collection';

    public function handle()
    {
        $chunkSize = 1000;

        $this->info("Updating follower/following counts for all users...");

        $updated = 0;

        User::select('id')->chunk($chunkSize, function ($users) use (&$updated) {
            foreach ($users as $user) {
                $followerCount = Follow::where('followed_id', $user->id)->count();
                $followingCount = Follow::where('follower_id', $user->id)->count();

                UserStats::where('user_id', $user->id)->update([
                    'follower_count' => $followerCount,
                    'following_count' => $followingCount,
                ]);

                $updated++;
            }
        });
        
        $this->info("Follower/following counts updated for {$updated} users.");

        return 0;
    }
}
