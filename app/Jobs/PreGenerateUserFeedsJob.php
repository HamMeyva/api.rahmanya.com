<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\VideoService;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PreGenerateUserFeedsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 60;
    
    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1; // Only try once since this is not critical
    
    /**
     * User to generate feeds for
     * 
     * @var User
     */
    protected $user;
    
    /**
     * Page number to generate
     * 
     * @var int
     */
    protected $page;
    
    /**
     * Items per page
     * 
     * @var int
     */
    protected $perPage;
    
    /**
     * Feed type to generate
     * 
     * @var string
     */
    protected $feedType;

    /**
     * Create a new job instance.
     *
     * @param User|string $user User object or user ID
     * @param int $page Page number to generate
     * @param int $perPage Items per page
     * @param string $feedType Feed type to generate
     * @return void
     */
    public function __construct($user, int $page = 1, int $perPage = 10, string $feedType = 'personalized')
    {
        if (is_string($user)) {
            $this->user = User::find($user);
        } else {
            $this->user = $user;
        }
        
        $this->page = $page;
        $this->perPage = $perPage;
        $this->feedType = $feedType;
    }

    /**
     * Execute the job.
     *
     * @param VideoService $videoService
     * @return void
     */
    public function handle(VideoService $videoService)
    {
        if (!$this->user) {
            Log::warning("PreGenerateUserFeedsJob: User not found");
            return;
        }
        
        Log::info("PreGenerating {$this->feedType} feed for user {$this->user->id}", [
            'page' => $this->page,
            'per_page' => $this->perPage,
            'feed_type' => $this->feedType
        ]);
        
        try {
            $result = null;
            
            // Generate the appropriate feed based on feedType
            switch ($this->feedType) {
                case 'personalized':
                    $result = $videoService->generatePersonalizedFeed($this->user, [
                        'page' => $this->page,
                        'per_page' => $this->perPage,
                        'bypass_cache' => true // Force regeneration
                    ]);
                    break;
                    
                case 'following':
                    $result = $videoService->generateFollowingFeed($this->user, [
                        'page' => $this->page,
                        'per_page' => $this->perPage,
                        'bypass_cache' => true // Force regeneration
                    ]);
                    break;
                    
                case 'sport':
                    $result = $videoService->generateSportFeed($this->user, [
                        'page' => $this->page,
                        'per_page' => $this->perPage,
                        'bypass_cache' => true // Force regeneration
                    ]);
                    break;
                    
                default:
                    $result = $videoService->generatePersonalizedFeed($this->user, [
                        'page' => $this->page,
                        'per_page' => $this->perPage,
                        'bypass_cache' => true // Force regeneration
                    ]);
                    break;
            }
            
            if ($result) {
                Log::info("Successfully pre-generated {$this->feedType} feed for user {$this->user->id}", [
                    'page' => $this->page,
                    'per_page' => $this->perPage,
                    'video_count' => count($result['videos'] ?? [])
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Error pre-generating {$this->feedType} feed for user {$this->user->id}", [
                'error' => $e->getMessage(),
                'page' => $this->page,
                'per_page' => $this->perPage,
                'feed_type' => $this->feedType,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
