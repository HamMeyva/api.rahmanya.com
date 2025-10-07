<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AgoraChannelMessageResource extends JsonResource
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
            'content' => $this->content,
            'type' => $this->type,
            'is_pinned' => (bool) $this->is_pinned,
            'is_blocked' => (bool) $this->is_blocked,
            'has_gift' => (bool) $this->has_gift,
            'gift_data' => $this->when($this->has_gift, $this->gift_data),
            'has_sticker' => (bool) $this->has_sticker,
            'sticker_data' => $this->when($this->has_sticker, $this->sticker_data),
            'parent_message_id' => $this->parent_message_id,
            'created_at' => isset($this->created_at) ? new \DateTime($this->created_at) : null,
            'updated_at' => isset($this->updated_at) ? new \DateTime($this->updated_at) : null,
            'user' => $this->user_data ? new UserBasicResource((object) $this->user_data) : null,
            'replies_count' => $this->when(isset($this->replies_count), $this->replies_count),
        ];
    }
}
