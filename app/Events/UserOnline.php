<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UserOnline implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public string $userId)
    {
        //
    }

    public function broadcastOn(): array
    {
        Log::info("UserOnline event broadcastOn userId: " . $this->userId);
        return [
            new PrivateChannel("App.Models.User.{$this->userId}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
        ];
    }
}
