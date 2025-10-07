<?php

namespace App\Jobs\Video;

use Exception;
use App\Models\Video;
use App\Services\BunnyCdnService;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateVideoDuration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected Video $video){}

    public function handle(): void
    {
        try {
            $bunnyCdnService = app(BunnyCdnService::class);
            $getVideo = $bunnyCdnService->getVideo($this->video->video_guid);

            $this->video->duration = $getVideo['length'] ?? 0;
            $this->video->save();
        } catch (Exception $e) {
            Log::error('UpdateVideoDuration: failed.', [
                'video_id' => $this->video->id,
                'video_guid' => $this->video->video_guid,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
