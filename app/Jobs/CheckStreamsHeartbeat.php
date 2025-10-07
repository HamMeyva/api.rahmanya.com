<?php

namespace App\Jobs;

use App\Models\Agora\AgoraChannel;
use App\Services\LiveStream\AgoraChannelService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CheckStreamsHeartbeat implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct() {}

    public function handle(): void
    {
        $streams = AgoraChannel::query()
            ->active()
            ->get();

        $service = app(AgoraChannelService::class);
        foreach ($streams as $stream) {
            $lastHeartbeat = Cache::get("stream_heartbeat_{$stream->id}");

            if (!$lastHeartbeat || now()->diffInSeconds($lastHeartbeat) > 30) {
                //@todo burayı aç yayın testi için kapattım yayını kontrol ediyorum !!!
                //$service->endStream($stream);
            }
        }
    }
}
