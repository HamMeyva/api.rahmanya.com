<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserFollowed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;
    // Removed SerializesModels to prevent automatic model serialization

    public $followId;

    /**
     * Create a new event instance.
     * @param int|string $followId The ID of the follow record
     */
    public function __construct($followId)
    {
        $this->followId = $followId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {
        $follow = $this->getFollowModel();
        if (!$follow) {
            \Log::error('UserFollowed: Could not find follow record with ID ' . $this->followId);
            return [];
        }
        
        return [new PrivateChannel('user.' . $follow->followed_id)];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith()
    {
        $follow = $this->getFollowModel();
        if (!$follow) {
            \Log::error('UserFollowed: Could not find follow record with ID ' . $this->followId);
            return ['error' => 'Follow record not found'];
        }
        
        $follower = \App\Models\User::find($follow->follower_id);
        
        return [
            'follow' => [
                'id' => $follow->id,
                'follower_id' => $follow->follower_id,
                'followed_id' => $follow->followed_id,
                'created_at' => $follow->created_at,
                'follower' => $follower ? [
                    'id' => $follower->id,
                    'name' => $follower->name,
                    'surname' => $follower->surname,
                    'nickname' => $follower->nickname,
                    'avatar' => $follower->avatar
                ] : null
            ]
        ];
    }
    
    /**
     * Get the follow model from the database
     * 
     * @return \App\Models\Follow|null
     */
    protected function getFollowModel()
    {
        try {
            return \App\Models\Follow::find($this->followId);
        } catch (\Exception $e) {
            \Log::error('UserFollowed: Error finding follow record: ' . $e->getMessage());
            return null;
        }
    }
}
