<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AgoraChannelResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'thumbnail_url' => $this->thumbnail_url,
            'channel_name' => $this->channel_name,
            'rtmp_url' => $this->rtmp_url,
            'playback_url' => $this->playback_url,
            'stream_key' => $this->when(
                $request->user() && $request->user()->id === $this->user_id, 
                $this->stream_key
            ),
            'viewer_count' => $this->viewer_count,
            'max_viewer_count' => $this->max_viewer_count,
            'total_likes' => $this->total_likes,
            'total_gifts' => $this->total_gifts,
            'total_coins_earned' => $this->total_coins_earned,
            'tags' => $this->tags,
            'is_featured' => (bool) $this->is_featured,
            'is_online' => (bool) $this->is_online,
            'status' => $this->status,
            'started_at' => $this->started_at ? $this->started_at->toIso8601String() : null,
            'ended_at' => $this->ended_at ? $this->ended_at->toIso8601String() : null,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'settings' => $this->settings,
            'user' => new UserBasicResource($this->whenLoaded('user')),
            'category' => new LiveStreamCategoryResource($this->whenLoaded('category')),
        ];
    }
}
