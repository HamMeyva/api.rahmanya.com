<?php

namespace App\Http\Controllers;

use App\Models\LiveStream;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ZegoMixerWebhookController extends Controller
{
    /**
     * Handle Zego mixer webhook
     */
    public function handleMixerWebhook(Request $request)
    {
        try {
            Log::info('Zego Mixer Webhook received', [
                'data' => $request->all()
            ]);

            $data = $request->all();

            // Zego mixer webhook payload
            $taskId = $data['task_id'] ?? null;
            $mixStreamId = $data['mix_stream_id'] ?? null;
            $roomId = $data['room_id'] ?? null;
            $status = $data['status'] ?? null;
            $inputStreams = $data['input_streams'] ?? [];

            if (!$taskId || !$mixStreamId) {
                return response()->json(['error' => 'Invalid webhook data'], 400);
            }

            // Find host and cohost streams
            $hostStreamId = null;
            $cohostStreamId = null;

            foreach ($inputStreams as $stream) {
                $streamId = $stream['stream_id'] ?? '';
                if (strpos($streamId, 'cohost_') !== false) {
                    $cohostStreamId = $streamId;
                } else {
                    $hostStreamId = $streamId;
                }
            }

            // Determine if mixer is active or stopped
            $isActive = ($status === 'active' || $status === 'started');
            $isStopped = ($status === 'stopped' || $status === 'ended');

            // Store mixed stream info
            DB::table('mixed_streams')->updateOrInsert(
                ['task_id' => $taskId],
                [
                    'mixed_stream_id' => $mixStreamId,
                    'room_id' => $roomId,
                    'host_stream_id' => $hostStreamId,
                    'cohost_stream_id' => $cohostStreamId,
                    'is_active' => $isActive,
                    'updated_at' => now()
                ]
            );

            // Handle mixer started
            if ($isActive && $hostStreamId) {
                LiveStream::where('stream_id', $hostStreamId)
                    ->orWhere('channel_name', $hostStreamId)
                    ->update([
                        'mixed_stream_id' => $mixStreamId,
                        'has_mixed_stream' => true,
                        'updated_at' => now()
                    ]);

                // Broadcast mixed stream availability
                $this->broadcastMixedStreamUpdate($roomId, $mixStreamId, true);
            }

            // Handle mixer stopped/ended - Clean up streams
            if ($isStopped) {
                Log::info('Mixer stopped - Cleaning up streams', [
                    'task_id' => $taskId,
                    'room_id' => $roomId,
                    'host_stream' => $hostStreamId,
                    'cohost_stream' => $cohostStreamId
                ]);

                // Update LiveStream records - remove mixed stream info
                if ($hostStreamId) {
                    LiveStream::where('stream_id', $hostStreamId)
                        ->orWhere('channel_name', $hostStreamId)
                        ->update([
                            'mixed_stream_id' => null,
                            'has_mixed_stream' => false,
                            'updated_at' => now()
                        ]);
                }

                // Also clean up cohost streams
                if ($cohostStreamId) {
                    LiveStream::where('stream_id', $cohostStreamId)
                        ->orWhere('channel_name', $cohostStreamId)
                        ->update([
                            'mixed_stream_id' => null,
                            'has_mixed_stream' => false,
                            'updated_at' => now()
                        ]);
                }

                // Clean up all streams from input_streams
                foreach ($inputStreams as $stream) {
                    $streamId = $stream['stream_id'] ?? '';
                    if ($streamId) {
                        LiveStream::where('stream_id', $streamId)
                            ->orWhere('channel_name', $streamId)
                            ->update([
                                'mixed_stream_id' => null,
                                'has_mixed_stream' => false,
                                'updated_at' => now()
                            ]);
                    }
                }

                // Broadcast that mixer has stopped
                $this->broadcastMixedStreamUpdate($roomId, $mixStreamId, false);

                Log::info('Mixer cleanup completed', [
                    'task_id' => $taskId,
                    'room_id' => $roomId
                ]);
            }

            Log::info('Zego Mixer Webhook processed', [
                'status' => $status,
                'task_id' => $taskId,
                'mixed_stream_id' => $mixStreamId,
                'room_id' => $roomId,
                'host_stream' => $hostStreamId,
                'cohost_stream' => $cohostStreamId,
                'is_active' => $isActive
            ]);

            return response()->json([
                'success' => true,
                'mixed_stream_id' => $mixStreamId
            ]);

        } catch (\Exception $e) {
            Log::error('Zego Mixer Webhook error', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json([
                'error' => 'Failed to process webhook',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Broadcast mixed stream update via WebSocket
     */
    private function broadcastMixedStreamUpdate($roomId, $mixedStreamId, $isActive)
    {
        try {
            broadcast(new \App\Events\MixedStreamUpdate([
                'room_id' => $roomId,
                'mixed_stream_id' => $mixedStreamId,
                'is_active' => $isActive,
                'timestamp' => now()->toIso8601String()
            ]))->toOthers();
        } catch (\Exception $e) {
            Log::error('Failed to broadcast mixed stream update', [
                'error' => $e->getMessage(),
                'room_id' => $roomId,
                'mixed_stream_id' => $mixedStreamId
            ]);
        }
    }

    /**
     * Get mixed stream for a room
     */
    public function getMixedStream($roomId)
    {
        try {
            $mixedStream = DB::table('mixed_streams')
                ->where('room_id', $roomId)
                ->where('is_active', true)
                ->orderBy('updated_at', 'desc')
                ->first();

            if ($mixedStream) {
                return response()->json([
                    'success' => true,
                    'mixed_stream_id' => $mixedStream->mixed_stream_id,
                    'task_id' => $mixedStream->task_id,
                    'is_active' => true
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No active mixed stream found'
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting mixed stream', [
                'error' => $e->getMessage(),
                'room_id' => $roomId
            ]);

            return response()->json([
                'error' => 'Failed to get mixed stream',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}