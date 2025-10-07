<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LiveStreamCategoryResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'icon' => $this->icon,
            'parent_id' => $this->parent_id,
            'display_order' => $this->display_order,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'parent' => $this->when($this->relationLoaded('parent'), function() {
                return [
                    'id' => $this->parent->id,
                    'name' => $this->parent->name,
                    'slug' => $this->parent->slug
                ];
            }),
            'streams_count' => $this->when($this->streams_count !== null, $this->streams_count),
            'subcategories_count' => $this->when($this->subcategories_count !== null, $this->subcategories_count),
        ];
    }
}
