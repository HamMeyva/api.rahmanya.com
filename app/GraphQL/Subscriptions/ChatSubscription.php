<?php

namespace App\GraphQL\Subscriptions;

use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Events\UserTyping;
use App\Models\Chat\Conversation;
use App\Models\Chat\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Nuwave\Lighthouse\Schema\Types\GraphQLSubscription;
use Nuwave\Lighthouse\Subscriptions\Subscriber;

class ChatSubscription extends GraphQLSubscription
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
        $user = $request->user();

        // Only authenticated users can subscribe
        if (!$user) {
            return false;
        }

        // For conversation-specific subscriptions, check if user is a participant
        $conversationId = $subscriber->args['conversationId'] ?? null;

        if ($conversationId) {
            $conversation = Conversation::find($conversationId);

            if (!$conversation) {
                return false;
            }

            return $conversation->isParticipant($user->id);
        }

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
        // Only send to subscribers of the specific conversation
        $subscribedToConversationId = $subscriber->args['conversationId'] ?? null;
        $eventConversationId = $root['conversation_id'] ?? null;

        return $subscribedToConversationId === $eventConversationId;
    }

    /**
     * Resolve the subscription for the subscriber.
     *
     * @param  mixed  $root
     * @param  array  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @param  \GraphQL\Type\Definition\ResolveInfo  $resolveInfo
     * @return mixed
     */
    public function resolve(mixed $root, array $args, \Nuwave\Lighthouse\Support\Contracts\GraphQLContext $context, \GraphQL\Type\Definition\ResolveInfo $resolveInfo): mixed
    {
        // Return the data as is
        return $root;
    }

    /**
     * Handle message sent subscription
     *
     * @param $root
     * @param array $args
     * @param $context
     * @param $info
     * @return array
     */
    public function messageSent($root, array $args, $context, $info)
    {
        // The $root will be the event payload from MessageSent
        $message = $root->message;

        // Format the message for GraphQL response
        return [
            'id' => (string)$message->_id,
            'conversation_id' => (string)$message->conversation_id,
            'sender_id' => (string)$message->sender_id,
            'content' => $message->content,
            'type' => $message->type,
            'media_url' => $message->media_url ?? null,
            'thumbnail_url' => $message->thumbnail_url ?? null,
            'duration' => $message->duration ?? null,
            'is_read' => $message->is_read,
            'read_at' => $message->read_at,
            'reply_to' => $message->reply_to ? (string)$message->reply_to : null,
            'reactions' => $message->reactions ?? [],
            'created_at' => $message->created_at,
            'sender' => $message->getSender()
        ];
    }

    /**
     * Handle message read subscription
     *
     * @param $root
     * @param array $args
     * @param $context
     * @param $info
     * @return array
     */
    public function messageRead($root, array $args, $context, $info)
    {
        // The $root will be the event payload from MessageRead
        $message = $root->message;

        // Format the message for GraphQL response
        return [
            'id' => (string)$message->_id,
            'conversation_id' => (string)$message->conversation_id,
            'sender_id' => (string)$message->sender_id,
            'content' => $message->content,
            'type' => $message->type,
            'media_url' => $message->media_url ?? null,
            'thumbnail_url' => $message->thumbnail_url ?? null,
            'duration' => $message->duration ?? null,
            'is_read' => true,
            'read_at' => now(),
            'reply_to' => $message->reply_to ? (string)$message->reply_to : null,
            'reactions' => $message->reactions ?? [],
            'created_at' => $message->created_at,
            'sender' => $message->getSender()
        ];
    }

    /**
     * Handle user typing subscription
     *
     * @param $root
     * @param array $args
     * @param $context
     * @param $info
     * @return array
     */
    public function userTyping($root, array $args, $context, $info)
    {
        // The $root will be the event payload from UserTyping
        return [
            'user_id' => (string)$root->userId,
            'is_typing' => $root->isTyping,
            'timestamp' => now()
        ];
    }
}
