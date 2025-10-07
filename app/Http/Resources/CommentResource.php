<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    /**
     * Kaynağı dizi formatına dönüştürür.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'          => $this->id,
            'user_id'     => $this->user_id,
            'comment'     => $this->comment,
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
            // Eğer yorumla ilişkili kullanıcı bilgilerini de sunmak isterseniz:
            'user'        => new UserResource($this->whenLoaded('user')),
        ];
    }
}
