<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AgoraChannelViewerResource extends JsonResource
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
            'id' => $this->_id,
            'agora_channel_id' => $this->agora_channel_id,
            'user_id' => $this->user_id,
            'role' => $this->role,
            'joined_at' => isset($this->joined_at) ? new \DateTime($this->joined_at) : null,
            'last_active_at' => isset($this->last_active_at) ? new \DateTime($this->last_active_at) : null,
            'viewing_duration' => $this->viewing_duration,
            'device_info' => $this->device_info,
            'is_following' => (bool) $this->is_following,
            'is_blocked' => (bool) $this->is_blocked,
            'total_gifts_sent' => $this->total_gifts_sent,
            'total_coins_spent' => $this->total_coins_spent,
            'created_at' => isset($this->created_at) ? new \DateTime($this->created_at) : null,
            'updated_at' => isset($this->updated_at) ? new \DateTime($this->updated_at) : null,
            'user' => $this->user_data ? new UserBasicResource((object) $this->user_data) : null,
        ];
    }
}
