<?php

namespace App\Jobs;

use App\Services\VideoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateUserFeedsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    protected $feedType;
    protected $page;
    protected $perPage;

    /**
     * Create a new job instance.
     *
     * @param string $userId User ID to update feeds for
     * @param string $feedType Type of feed (following, sport, user_own, personalized)
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return void
     */
    public function __construct(string $userId, string $feedType, int $page = 1, int $perPage = 15)
    {
        $this->userId = $userId;
        $this->feedType = $feedType;
        $this->page = $page;
        $this->perPage = $perPage;
    }

    /**
     * Execute the job.
     *
     * @param VideoService $videoService
     * @return void
     */
    public function handle(VideoService $videoService)
    {
        try {
            Log::info('Starting user feed update job', [
                'user_id' => $this->userId,
                'feed_type' => $this->feedType,
                'page' => $this->page
            ]);

            // Find user or fail
            $user = \App\Models\User::find($this->userId);
            if (!$user) {
                Log::warning('User not found for feed update job', ['user_id' => $this->userId]);
                return;
            }

            // Set bypass cache to force regeneration
            $options = [
                'page' => $this->page,
                'per_page' => $this->perPage,
                'bypass_cache' => true, // Force regeneration
            ];

            // Handle different feed types
            switch ($this->feedType) {
                case 'following':
                    $videoService->generateFollowingFeed($user, $options);
                    break;
                case 'sport':
                    $videoService->generateSportFeed($user, $options);
                    break;
                case 'user_own':
                    $videoService->generateUserOwnVideos($user, $options);
                    break;
                case 'personalized':
                    $videoService->generatePersonalizedFeed($user, $options);
                    break;
                default:
                    Log::warning('Unknown feed type', ['feed_type' => $this->feedType]);
                    break;
            }

            Log::info('Completed user feed update job', [
                'user_id' => $this->userId,
                'feed_type' => $this->feedType
            ]);
        } catch (\Exception $e) {
            Log::error('Error in user feed update job', [
                'user_id' => $this->userId,
                'feed_type' => $this->feedType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
