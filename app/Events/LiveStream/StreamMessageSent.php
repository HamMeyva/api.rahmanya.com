<?php

namespace App\Events\LiveStream;

use App\Models\Agora\AgoraChannel;
use App\Models\Agora\AgoraChannelMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StreamMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $messageData;

    public function __construct(public AgoraChannel $stream, public AgoraChannelMessage $message)
    {
        $this->stream = $stream;
        $this->message = $message;
        
        $this->messageData = [
            'id' => $message->_id,
            'agora_channel_id' => $message->agora_channel_id,
            'agora_channel_data' => $message->agora_channel_data,
            'user_id' => $message->user_id,
            'user_data' => $message->user_data,
            'admin_id' => $message->admin_id,
            'admin_data' => $message->admin_data,
            'message' => $message->message,
            'timestamp' => $message->timestamp,

            'is_pinned' => (bool) $message->is_pinned,
            'is_blocked' => (bool) $message->is_blocked,

            'gift_id' => $message->gift_id ?? null,
            'gift_data' => $message->gift_data ?? null,
            'parent_message_id' => $message->parent_message_id ?? null,
        ];
    }

    public function broadcastOn()
    {
        return new PrivateChannel("live-stream.{$this->stream->id}.chat");
    }

    public function broadcastWith()
    {
        return $this->messageData;
    }
}
