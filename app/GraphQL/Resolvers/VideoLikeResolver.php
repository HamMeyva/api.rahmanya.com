<?php

namespace App\GraphQL\Resolvers;

use App\Models\User;
use App\Models\VideoLike;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class VideoLikeResolver
{
    /**
     * Resolve user for a video like (handles cross-database relationship)
     * This method is used to manually resolve the user relationship
     * since traditional Eloquent relationships don't work between MongoDB and SQL
     */
    public function resolveUser($videoLike, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        // Directly fetch the user from the SQL database using the user_id from the MongoDB document
        return User::find($videoLike->user_id);
    }
    
    /**
     * Resolve video for a video like (handles MongoDB relationship)
     * This method is used to manually resolve the video relationship
     */
    public function resolveVideo($videoLike, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        // Directly fetch the video from MongoDB
        return \App\Models\Video::find($videoLike->video_id);
    }
}
