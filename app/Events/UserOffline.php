<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UserOffline
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public string $userId)
    {
        //
    }

    public function broadcastOn(): array
    {
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
