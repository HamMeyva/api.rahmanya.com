<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TestUserChannel implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public $userId) {}

    public function broadcastOn()
    {
        return [new PrivateChannel("App.Models.User.{$this->userId}")];
    }

    public function broadcastWith()
    {
        return [
            'message' => 'test mesajıdır. !!!',
            'rand' => rand(1, 100000000),
        ];
    }
}
