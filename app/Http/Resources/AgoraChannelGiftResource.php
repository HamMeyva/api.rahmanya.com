<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AgoraChannelGiftResource extends JsonResource
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
            'gift_id' => $this->gift_id,
            'coin_value' => $this->coin_value,
            'quantity' => $this->quantity,
            'message' => $this->message,
            'is_featured' => (bool) $this->is_featured,
            'streak' => $this->streak,
            'created_at' => isset($this->created_at) ? new \DateTime($this->created_at) : null,
            'updated_at' => isset($this->updated_at) ? new \DateTime($this->updated_at) : null,
            'user' => $this->user_data ? new UserBasicResource((object) $this->user_data) : null,
            'gift' => $this->gift_data,
        ];
    }
}
