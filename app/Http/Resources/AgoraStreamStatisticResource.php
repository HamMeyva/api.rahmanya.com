<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AgoraStreamStatisticResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'agora_channel_id' => $this->agora_channel_id,
            'user_id' => $this->user_id,
            'date' => $this->date->toDateString(),
            'total_stream_duration' => $this->total_stream_duration,
            'max_concurrent_viewers' => $this->max_concurrent_viewers,
            'total_viewers' => $this->total_viewers,
            'new_followers' => $this->new_followers,
            'total_likes' => $this->total_likes,
            'total_comments' => $this->total_comments,
            'total_gifts' => $this->total_gifts,
            'total_coins_earned' => $this->total_coins_earned,
            'device_analytics' => $this->device_analytics,
            'location_analytics' => $this->location_analytics,
            'interaction_metrics' => $this->interaction_metrics,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'user' => new UserBasicResource($this->whenLoaded('user')),
            'agora_channel' => new AgoraChannelResource($this->whenLoaded('agoraChannel')),
        ];
    }
}
