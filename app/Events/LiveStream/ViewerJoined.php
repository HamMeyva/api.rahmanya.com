<?php

namespace App\Events\LiveStream;

use App\Models\Agora\AgoraChannelViewer;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ViewerJoined implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $viewerData;

    public function __construct(public AgoraChannelViewer $viewer)
    {
        $userData = null;
        if ($viewer->user_data) {
            $userData = [
                'id' => $viewer->user_data['id'] ?? $viewer->user_id,
                'nickname' => $viewer->user_data['nickname'] ?? null,
                'avatar' => $viewer->user_data['avatar'] ?? null,
                'is_verified' => isset($viewer->user_data['is_verified']) ? (bool) $viewer->user_data['is_verified'] : false
            ];
        }
        
        $this->viewerData = [
            'id' => $viewer->_id,
            'agora_channel_id' => $viewer->agora_channel_id,
            'user_id' => $viewer->user_id,
            'role_id' => $viewer->role_id,
            'get_role' => $viewer->get_role,
            'joined_at' => isset($viewer->joined_at) ? (string) $viewer->joined_at : null,
            'is_following' => (bool) $viewer->is_following,
            'user' => $userData
        ];
    }

    public function broadcastOn()
    {
        return new PrivateChannel("live-stream.{$this->viewer->agora_channel_id}");
    }

    public function broadcastWith(): array
    {
        return $this->viewerData;
    }
}
