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

class StreamMessagePinned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var AgoraChannel
     */
    public $stream;

    /**
     * @var AgoraChannelMessage
     */
    public $message;

    /**
     * @var int
     */
    public $pinnedBy;

    /**
     * Create a new event instance.
     *
     * @param AgoraChannel $stream
     * @param AgoraChannelMessage $message
     * @param int $pinnedBy
     */
    public function __construct(AgoraChannel $stream, AgoraChannelMessage $message, int $pinnedBy)
    {
        $this->stream = $stream;
        $this->message = $message;
        $this->pinnedBy = $pinnedBy;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel("live-stream.{$this->stream->id}.chat");
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'message.pinned';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'stream_id' => $this->stream->id,
            'message_id' => $this->message->_id,
            'message' => [
                'id' => $this->message->_id,
                'content' => $this->message->content,
                'user_id' => $this->message->user_id,
                'user' => $this->message->user_data,
                'created_at' => isset($this->message->created_at) ? (string) $this->message->created_at : null
            ],
            'pinned_by' => $this->pinnedBy,
            'pinned_at' => now()->toIso8601String()
        ];
    }
}
