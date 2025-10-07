<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VideoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id'               => $this->id,
            'title'            => $this->title ?? $this->name,
            'description'      => $this->description,
            'video_url'        => $this->video_url,
            'thumbnail_url'    => $this->thumbnail_url,
            'tags'             => $this->tags,
            'team_tags'        => $this->team_tags ?? [],
            'isPublic'         => $this->isPublic ?? !$this->is_private,
            'isCommentable'    => $this->isCommentable ?? $this->is_commentable,
            'created_at'       => $this->created_at,
            'updated_at'       => $this->updated_at,
            'user'             => new UserResource($this->whenLoaded('user')),
            'video_comments'   => CommentResource::collection($this->whenLoaded('video_comments')),
            'video_likes_count'=> $this->video_likes_count ?? $this->whenCounted('video_likes'),

            // New fields for TikTok-like functionality
            'duration'         => $this->duration,
            'views_count'      => $this->views_count ?? 0,
            'width'            => $this->width,
            'height'           => $this->height,
            'framerate'        => $this->framerate,
            'category'         => $this->category,
            'location'         => $this->location,
            'language'         => $this->language,
            'content_rating'   => $this->content_rating,
            'engagement_score' => $this->engagement_score,
            'is_featured'      => $this->is_featured ?? false,
            'status'           => $this->status ?? 'completed',

            // Conditional fields that should only be included if they exist
            'trending_score'   => $this->when($request->user() && $request->user()->hasRole('admin'), $this->trending_score),
            'processing_status'=> $this->when($request->user() && $request->user()->id === $this->user_id, $this->processing_status),
            'is_banned'        => $this->when($request->user() && $request->user()->hasRole('admin'), $this->is_banned),
        ];
    }
}
