<?php

namespace App\Services;

use App\Models\Agora\AgoraChannel;
use App\Models\MixerSession;
use App\Models\MixerParticipant;
use App\Models\MixerLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

/**
 * ZEGO Stream Mixer Service
 * Handles mixing multiple streams into a single output using ZEGO's RTC API
 */
class ZegoStreamMixerService
{
    private string $appId;
    private string $serverSecret;

    public function __construct()
    {
        $this->appId = config('services.zego.app_id', '825373332');
        $this->serverSecret = config('services.zego.server_secret', '25320a19b9ae22e056bd2c0f13b6bfa5');
    }

    /**
     * Start a new mixer task with ZEGO
     *
     * @param array $streamers Array of streamer info with stream_id, user_id, chat_room_id
     * @return array Result with success status and mixer info
     */
    public function startMixerTask(array $streamers): array
    {
        try {
            $taskId = 'mixer_' . time() . '_' . Str::random(8);
            $mixedStreamId = 'mixed_' . Str::uuid()->toString();

            // Prepare ZEGO API parameters
            $params = $this->buildMixerParams($taskId, $mixedStreamId, $streamers);

            // Call ZEGO API
            $response = $this->callZegoApi('StartMix', $params);

            if ($response['success']) {
                // Save to database
                $session = $this->saveMixerSession($taskId, $mixedStreamId, $streamers);

                return [
                    'success' => true,
                    'task_id' => $taskId,
                    'mixed_stream_id' => $mixedStreamId,
                    'session_id' => $session->id,
                    'message' => 'Mixer started successfully'
                ];
            }

            throw new Exception($response['error'] ?? 'Failed to start mixer');

        } catch (Exception $e) {
            Log::error('Start mixer task failed', [
                'error' => $e->getMessage(),
                'streamers' => $streamers
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update an existing mixer task (add/remove streams or change layout)
     */
    public function updateMixerTask(string $taskId, array $streamers): array
    {
        try {
            if (empty($streamers)) {
                return $this->stopMixerTask($taskId);
            }

            $session = MixerSession::where('task_id', $taskId)->first();
            if (!$session) {
                // If session doesn't exist, start a new one
                return $this->startMixerTask($streamers);
            }

            // Get mixed stream ID from session
            $mixedStreamId = $session->mixed_stream_id;

            // Update mixer with new configuration
            $params = $this->buildMixerParams($taskId, $mixedStreamId, $streamers);
            $response = $this->callZegoApi('StartMix', $params); // ZEGO uses StartMix for updates too

            if ($response['success']) {
                // Update database
                $this->updateMixerSession($session, $streamers);

                return [
                    'success' => true,
                    'task_id' => $taskId,
                    'message' => 'Mixer updated successfully'
                ];
            }

            throw new Exception($response['error'] ?? 'Failed to update mixer');

        } catch (Exception $e) {
            Log::error('Update mixer task failed', [
                'task_id' => $taskId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Stop a mixer task
     */
    public function stopMixerTask(string $taskId): array
    {
        try {
            $params = [
                'TaskId' => [[
                    'TaskId' => $taskId
                ]]
            ];

            $response = $this->callZegoApi('StopMix', $params);

            // Update database regardless of API response
            $session = MixerSession::where('task_id', $taskId)->first();
            if ($session) {
                $session->update(['status' => 'stopped']);
                MixerParticipant::where('mixer_session_id', $session->id)
                    ->whereNull('left_at')
                    ->update(['left_at' => now()]);
            }

            return [
                'success' => true,
                'message' => 'Mixer stopped successfully'
            ];

        } catch (Exception $e) {
            Log::error('Stop mixer task failed', [
                'task_id' => $taskId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Build mixer parameters for ZEGO API
     */
    private function buildMixerParams(string $taskId, string $mixedStreamId, array $streamers): array
    {
        $count = count($streamers);
        $inputList = [];

        // Build input stream list with layouts
        foreach ($streamers as $index => $streamer) {
            $layout = $this->calculateStreamLayout($index, $count);
            $inputList[] = [
                'StreamId' => $streamer['stream_id'],
                'RectInfo' => [
                    'Top' => $layout['top'],
                    'Left' => $layout['left'],
                    'Bottom' => $layout['bottom'],
                    'Right' => $layout['right']
                ]
            ];
        }

        return [
            'TaskId' => [[
                'TaskId' => $taskId,
                'RoomId' => $mixedStreamId, // Use mixed stream ID as room ID
                'MixInput' => $inputList,
                'MixOutput' => [[
                    'StreamId' => $mixedStreamId,
                    'VideoBitrate' => 1500, // 1.5 Mbps
                    'VideoFramerate' => 25,
                    'VideoWidth' => 1280,
                    'VideoHeight' => 720,
                    'AudioBitrate' => 128,
                    'AudioChannels' => 2,
                    'ExtraParams' => [
                        [
                            'Key' => 'mixMode',
                            'Value' => '1' // 1 = RTC output mode (stream to room)
                        ]
                    ]
                ]]
            ]]
        ];
    }

    /**
     * Calculate layout for a stream based on position and total count
     */
    private function calculateStreamLayout(int $index, int $total): array
    {
        $width = 1280;
        $height = 720;

        switch($total) {
            case 1:
                // Full screen
                return ['top' => 0, 'left' => 0, 'bottom' => $height, 'right' => $width];

            case 2:
                // Side by side
                $halfWidth = $width / 2;
                return [
                    'top' => 0,
                    'left' => $index * $halfWidth,
                    'bottom' => $height,
                    'right' => ($index + 1) * $halfWidth
                ];

            case 3:
                // One large on top, two small below
                if ($index === 0) {
                    return ['top' => 0, 'left' => 0, 'bottom' => $height / 2, 'right' => $width];
                } else {
                    $halfWidth = $width / 2;
                    return [
                        'top' => $height / 2,
                        'left' => ($index - 1) * $halfWidth,
                        'bottom' => $height,
                        'right' => $index * $halfWidth
                    ];
                }

            case 4:
                // 2x2 grid
                $halfWidth = $width / 2;
                $halfHeight = $height / 2;
                $row = floor($index / 2);
                $col = $index % 2;
                return [
                    'top' => $row * $halfHeight,
                    'left' => $col * $halfWidth,
                    'bottom' => ($row + 1) * $halfHeight,
                    'right' => ($col + 1) * $halfWidth
                ];

            default:
                // Default to grid for more than 4
                return $this->calculateStreamLayout($index % 4, 4);
        }
    }

    /**
     * Call ZEGO API with proper authentication
     */
    private function callZegoApi(string $action, array $params): array
    {
        try {
            $timestamp = time();
            $nonce = rand(100000, 999999);
            $signature = $this->generateSignature($timestamp, $nonce);

            $url = "https://rtc-api.zego.im/?Action={$action}";

            $headers = [
                'AppId' => $this->appId,
                'SignatureNonce' => (string)$nonce,
                'Timestamp' => (string)$timestamp,
                'Signature' => $signature,
                'SignatureVersion' => '2.0',
                'IsTest' => 'false'
            ];

            $response = Http::withHeaders($headers)->post($url, $params);

            $result = $response->json();

            if ($response->successful() && isset($result['Code']) && $result['Code'] == 0) {
                return ['success' => true, 'data' => $result['Data'] ?? []];
            }

            $errorMessage = $result['Message'] ?? 'Unknown error';
            Log::error('ZEGO API error', [
                'action' => $action,
                'code' => $result['Code'] ?? 'unknown',
                'message' => $errorMessage
            ]);

            return ['success' => false, 'error' => $errorMessage];

        } catch (Exception $e) {
            Log::error('ZEGO API call failed', [
                'action' => $action,
                'error' => $e->getMessage()
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Generate ZEGO API signature
     */
    private function generateSignature(int $timestamp, int $nonce): string
    {
        $signContent = $this->appId . $this->serverSecret . $timestamp . $nonce;
        return md5($signContent);
    }

    /**
     * Save mixer session to database
     */
    private function saveMixerSession(string $taskId, string $mixedStreamId, array $streamers): MixerSession
    {
        $session = MixerSession::create([
            'id' => Str::uuid(),
            'task_id' => $taskId,
            'mixed_stream_id' => $mixedStreamId,
            'mixed_stream_url' => '', // Not used in RTC mode
            'layout_type' => $this->getLayoutType(count($streamers)),
            'config' => [
                'streamers' => $streamers,
                'output_mode' => 'rtc'
            ],
            'status' => 'active'
        ]);

        // Save participants
        foreach ($streamers as $index => $streamer) {
            MixerParticipant::create([
                'id' => Str::uuid(),
                'mixer_session_id' => $session->id,
                'stream_id' => $streamer['stream_id'],
                'user_id' => $streamer['user_id'],
                'chat_room_id' => $streamer['chat_room_id'],
                'position' => $index
            ]);
        }

        return $session;
    }

    /**
     * Update mixer session in database
     */
    private function updateMixerSession(MixerSession $session, array $streamers): void
    {
        // Mark old participants as left
        MixerParticipant::where('mixer_session_id', $session->id)
            ->whereNull('left_at')
            ->update(['left_at' => now()]);

        // Add new participants
        foreach ($streamers as $index => $streamer) {
            MixerParticipant::create([
                'id' => Str::uuid(),
                'mixer_session_id' => $session->id,
                'stream_id' => $streamer['stream_id'],
                'user_id' => $streamer['user_id'],
                'chat_room_id' => $streamer['chat_room_id'],
                'position' => $index
            ]);
        }

        // Update session
        $session->update([
            'layout_type' => $this->getLayoutType(count($streamers)),
            'config' => [
                'streamers' => $streamers,
                'output_mode' => 'rtc'
            ]
        ]);
    }

    /**
     * Get layout type name based on count
     */
    private function getLayoutType(int $count): string
    {
        return match($count) {
            1 => 'single',
            2 => 'side_by_side',
            3 => 'three_way',
            4 => 'grid',
            default => 'grid'
        };
    }

    /**
     * Get active mixer session for a stream
     */
    public function getActiveMixerForStream(string $streamId): ?array
    {
        $participant = MixerParticipant::where('stream_id', $streamId)
            ->whereNull('left_at')
            ->with(['mixerSession' => function($query) {
                $query->where('status', 'active');
            }])
            ->first();

        if (!$participant || !$participant->mixerSession) {
            return null;
        }

        $session = $participant->mixerSession;
        $participants = MixerParticipant::where('mixer_session_id', $session->id)
            ->whereNull('left_at')
            ->with('user')
            ->get();

        return [
            'task_id' => $session->task_id,
            'mixed_stream_id' => $session->mixed_stream_id,
            'layout_type' => $session->layout_type,
            'participants' => $participants->map(function($p) {
                return [
                    'stream_id' => $p->stream_id,
                    'user_id' => $p->user_id,
                    'chat_room_id' => $p->chat_room_id,
                    'position' => $p->position,
                    'username' => $p->user->username ?? 'Unknown'
                ];
            })->toArray()
        ];
    }
}