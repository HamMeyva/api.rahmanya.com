<?php

namespace App\GraphQL\Subscriptions;

use App\Models\VideoLike;
use Illuminate\Http\Request;
use Nuwave\Lighthouse\Schema\Types\GraphQLSubscription;
use Nuwave\Lighthouse\Subscriptions\Subscriber;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;

class VideoLikeSubscription extends GraphQLSubscription
{
    /**
     * Check if subscriber is allowed to listen to the subscription.
     *
     * @param  \Nuwave\Lighthouse\Subscriptions\Subscriber  $subscriber
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function authorize(Subscriber $subscriber, Request $request): bool
    {
        // Anyone can subscribe to video likes
        return true;
    }

    /**
     * Filter which subscribers should receive the subscription.
     *
     * @param  \Nuwave\Lighthouse\Subscriptions\Subscriber  $subscriber
     * @param  mixed  $root
     * @return bool
     */
    public function filter(Subscriber $subscriber, $root): bool
    {
        // Only send to subscribers who are watching the specific video
        $videoId = $subscriber->args['videoId'] ?? null;
        
        // If the subscriber didn't specify a videoId, don't send
        if (!$videoId) {
            return false;
        }
        
        // Make sure the root is a VideoLike
        if (!$root instanceof VideoLike) {
            return false;
        }
        
        // Only send if the like is for the video they're watching
        return $root->video_id == $videoId;
    }

    /**
     * Resolve the subscription.
     *
     * @param  mixed  $root
     * @param  array  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @param  \GraphQL\Type\Definition\ResolveInfo  $resolveInfo
     * @return mixed
     */
    public function resolve(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): mixed
    {
        // Return the like with relationships
        return $root->load(['user', 'video']);
    }
}
