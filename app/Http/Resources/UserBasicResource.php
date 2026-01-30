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
        $resource = $this->resource;

        return [
            'id' => data_get($resource, 'id') ?? data_get($resource, '_id'),
            'nickname' => data_get($resource, 'nickname'),
            'avatar' => data_get($resource, 'avatar'),
            'bio' => data_get($resource, 'bio'),
            'followers_count' => (int) data_get($resource, 'followers_count', 0),
            'is_verified' => (bool) data_get($resource, 'is_verified', false),
            'created_at' => null, // Safely ignore date for basic resource to prevent parse errors
        ];
    }
}
