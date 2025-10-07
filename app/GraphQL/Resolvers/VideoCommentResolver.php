<?php

namespace App\GraphQL\Resolvers;

use Exception;
use App\Models\User;
use App\Models\VideoComment;
use App\Models\VideoCommentReaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use GraphQL\Type\Definition\ResolveInfo;
use App\Notifications\CommentLikedNotification;
use App\Notifications\CommentDislikedNotification;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class VideoCommentResolver
{
    /**
     * Resolve likes count for a video comment
     */
    public function resolveLikesCount($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        return $root->getLikesCountAttribute();
    }

    /**
     * Resolve dislikes count for a video comment
     */
    public function resolveDislikesCount($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        return $root->getDislikesCountAttribute();
    }

    /**
     * Resolve if the current user has liked this video comment
     */
    public function resolveIsLikedByMe($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        return $root->isLikedByUser($user->id);
    }

    /**
     * Resolve if the current user has disliked this video comment
     */
    public function resolveIsDislikedByMe($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        return $root->isDislikedByUser($user->id);
    }

    public function likeVideoComment($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = Auth::user();
        $commentId = $args['id'];

        $comment = VideoComment::with('video')->find($commentId);
        if (!$comment) {
            throw new Exception('Yorum bulunamadı.');
        }

        $videoCommentReaction = VideoCommentReaction::where('comment_id', $commentId)
            ->where('user_id', $user->id)
            ->first();

        $liked = false;
        if ($videoCommentReaction) {
            if ($videoCommentReaction->reaction_type !== 'like') {
                $videoCommentReaction->update(['reaction_type' => 'like']);
                $liked = true;
            }
        } else {
            $videoCommentReaction = VideoCommentReaction::create([
                'user_id' => $user->id,
                'comment_id' => $commentId,
                'reaction_type' => 'like'
            ]);
            $liked = true;
        }

        if ($liked) {
            $comment->user()->notify(new CommentLikedNotification($videoCommentReaction));
            
            // Önbellek temizleme işlemi
            try {
                // Beğeni/beğenmeme önbelleklerini temizle
                $likesCacheKey = "comment_{$comment->id}_likes_count";
                $dislikesCacheKey = "comment_{$comment->id}_dislikes_count";
                \Illuminate\Support\Facades\Cache::forget($likesCacheKey);
                \Illuminate\Support\Facades\Cache::forget($dislikesCacheKey);
                
                // Video engagement score önbelleğini temizle
                if ($comment->video) {
                    $videoDetailCacheKey = "video:{$comment->video->id}";
                    \Illuminate\Support\Facades\Cache::forget($videoDetailCacheKey);
                }
            } catch (\Exception $cacheException) {
                // Cache temizleme hatası olsa bile ana işlemi etkilememeli
                \Illuminate\Support\Facades\Log::error('Cache invalidation error: ' . $cacheException->getMessage());
            }
        }

        return $comment;
    }

  
    public function dislikeVideoComment($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = Auth::user();
        $commentId = $args['id'];

        $comment = VideoComment::with('video')->find($commentId);
        if (!$comment) {
            throw new Exception('Yorum bulunamadı.');
        }

        $videoCommentReaction = VideoCommentReaction::where('comment_id', $commentId)
            ->where('user_id', $user->id)
            ->first();

        $disliked = false;
        if ($videoCommentReaction) {
            if ($videoCommentReaction->reaction_type !== 'dislike') {
                $videoCommentReaction->update(['reaction_type' => 'dislike']);
                $disliked = true;
            }
        } else {
            $videoCommentReaction = VideoCommentReaction::create([
                'user_id' => $user->id,
                'comment_id' => $commentId,
                'reaction_type' => 'dislike'
            ]);
            $disliked = true;
        }

        if ($disliked) {
            $comment->user()->notify(new CommentDislikedNotification($videoCommentReaction));
            
            // Önbellek temizleme işlemi
            try {
                // Beğeni/beğenmeme önbelleklerini temizle
                $likesCacheKey = "comment_{$comment->id}_likes_count";
                $dislikesCacheKey = "comment_{$comment->id}_dislikes_count";
                \Illuminate\Support\Facades\Cache::forget($likesCacheKey);
                \Illuminate\Support\Facades\Cache::forget($dislikesCacheKey);
                
                // Video engagement score önbelleğini temizle
                if ($comment->video) {
                    $videoDetailCacheKey = "video:{$comment->video->id}";
                    \Illuminate\Support\Facades\Cache::forget($videoDetailCacheKey);
                }
            } catch (\Exception $cacheException) {
                // Cache temizleme hatası olsa bile ana işlemi etkilememeli
                \Illuminate\Support\Facades\Log::error('Cache invalidation error: ' . $cacheException->getMessage());
            }
        }

        return $comment;
    }

    /**
     * Remove reaction from a video comment
     */
    public function removeVideoCommentReaction($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = Auth::user();
        $commentId = $args['id'];

        $comment = VideoComment::find($commentId);
        if (!$comment) {
            throw new \Exception('Video comment not found');
        }

        // Remove any existing reactions from this user for this comment
        VideoCommentReaction::where('comment_id', $commentId)
            ->where('user_id', $user->id)
            ->delete();

        // GraphQL önbelleğini temizle
        \Illuminate\Support\Facades\Artisan::call('cache:clear');

        return $comment;
    }

    /**
     * Resolve user for a video comment (handles cross-database relationship)
     * This method is used to manually resolve the user relationship
     * since traditional Eloquent relationships don't work between MongoDB and SQL
     */
    public function resolveUser($videoComment, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        // Directly fetch the user from the SQL database using the user_id from the MongoDB document
        return User::find($videoComment->user_id);
    }

    /**
     * Resolve parent for a video comment (handles MongoDB relationship)
     * This method is used to manually resolve the parent relationship
     */
    public function resolveParent($videoComment, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        // Only try to fetch parent if parent_id exists
        if (!$videoComment->parent_id) {
            return null;
        }

        // Directly fetch the parent comment from MongoDB
        return \App\Models\VideoComment::find($videoComment->parent_id);
    }

    /**
     * Resolve video for a video comment (handles MongoDB relationship)
     * This method is used to manually resolve the video relationship
     */
    public function resolveVideo($videoComment, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        // Directly fetch the video from MongoDB
        return \App\Models\Video::find($videoComment->video_id);
    }
}
