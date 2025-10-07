<?php

namespace App\Jobs\Feed;

use App\Models\User;
use App\Services\Video\FeedService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;

class UpdateUserFeedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $userId,
        protected string $type = 'mixed',
        protected int $limit = 50,
        protected array $previousFeedVideoIds = []
    ) {}

    public function handle(FeedService $feedService): void
    {
        /** @var User $user */
        $user = User::find($this->userId);
        if (!$user) return;

        $feedService->updateFeedCache($user, $this->type, $this->limit, $this->previousFeedVideoIds);
    }
}
