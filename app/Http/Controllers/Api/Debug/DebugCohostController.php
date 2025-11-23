<?php

namespace App\Http\Controllers\Api\Debug;

use App\Http\Controllers\Controller;
use App\Models\Agora\AgoraChannel;
use Illuminate\Http\Request;

class DebugCohostController extends Controller
{
    /**
     * Check cohost stream data in MongoDB
     */
    public function checkCohostStream($streamId)
    {
        try {
            $stream = AgoraChannel::find($streamId);

            if (!$stream) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stream not found',
                    'stream_id' => $streamId,
                ], 404);
            }

            return response()->json([
                'success' => true,
                'stream_id' => $stream->id,
                'channel_name' => $stream->channel_name,
                'title' => $stream->title,
                'fields' => [
                    'is_cohost' => $stream->is_cohost ?? null,
                    'is_cohost_stream' => $stream->is_cohost_stream ?? null,
                    'parent_stream_id' => $stream->parent_stream_id ?? null,
                    'parent_channel_id' => $stream->parent_channel_id ?? null,
                    'host_stream_id' => $stream->host_stream_id ?? null,
                ],
                'all_attributes' => $stream->getAttributes(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }
}
