<?php

namespace App\Events\LiveStream;

use App\Models\Agora\AgoraChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class StreamStarted implements ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public AgoraChannel $stream)
    {}
}
