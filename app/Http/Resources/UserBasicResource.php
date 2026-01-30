<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserBasicResource extends JsonResource
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
            'nickname' => $this->nickname,
            'avatar' => $this->avatar,
            'bio' => $this->bio,
            'followers_count' => data_get($this->resource, 'followers_count'),
            'is_verified' => (bool) $this->is_verified,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
        ];
    }
}
