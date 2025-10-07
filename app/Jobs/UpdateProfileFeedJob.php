<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\VideoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateProfileFeedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $profileUserId;
    protected $page;
    protected $perPage;

    /**
     * Create a new job instance.
     *
     * @param string $profileUserId Profile user ID to update feed for
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return void
     */
    public function __construct(string $profileUserId, int $page = 1, int $perPage = 15)
    {
        $this->profileUserId = $profileUserId;
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
            Log::info('Starting profile feed update job', [
                'profile_user_id' => $this->profileUserId,
                'page' => $this->page
            ]);

            // Find profile user or fail
            $profileUser = User::find($this->profileUserId);
            if (!$profileUser) {
                Log::warning('Profile user not found for feed update job', ['profile_user_id' => $this->profileUserId]);
                return;
            }

            // Set bypass cache to force regeneration
            $options = [
                'page' => $this->page,
                'per_page' => $this->perPage,
                'bypass_cache' => true, // Force regeneration
            ];

            // Since profile videos can be viewed by others besides the profile owner,
            // we'll generate the public view first (as if viewed by profile owner)
            $videoService->generateProfileVideos($profileUser, $this->profileUserId, $options);

            Log::info('Completed profile feed update job', [
                'profile_user_id' => $this->profileUserId
            ]);
        } catch (\Exception $e) {
            Log::error('Error in profile feed update job', [
                'profile_user_id' => $this->profileUserId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
