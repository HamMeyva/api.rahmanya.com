<?php

namespace App\Services;

use App\Models\LiveStream;
use App\Models\MixerSession;
use App\Models\MixerParticipant;
use App\Models\MixerLog;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class ZegoMixerService
{
    private string $appId;
    private string $serverSecret;
    private string $apiUrl;
    private string $cdnUrl;

    public function __construct()
    {
        $this->appId = config('services.zego.app_id', '825373332');
        $this->serverSecret = config('services.zego.server_secret', '25320a19b9ae22e056bd2c0f13b6bfa5');
        $this->apiUrl = 'https://rtc-api.zego.im/?Action=StartMix';
        $this->cdnUrl = ''; // Not needed for RTC output mode
    }

    /**
     * Generate signature for ZEGO API authentication
     */
    private function generateSignature(int $timestamp, int $nonce): string
    {
        $signContent = $this->appId . $this->serverSecret . $timestamp . $nonce;
        return md5($signContent);
    }

    /**
     * Calculate layout type based on streamer count
     */
    private function calculateLayoutType(int $count): string
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
     * Generate input layout configuration for streamers
     */
    private function generateInputLayout(array $streamers): array
    {
        $count = count($streamers);
        $layouts = [];
        $width = 1280;
        $height = 720;

        switch($count) {
            case 1:
                $layouts[] = [
                    'streamID' => $streamers[0]['stream_id'],
                    'layout' => [
                        'top' => 0,
                        'left' => 0,
                        'bottom' => $height,
                        'right' => $width
                    ]
                ];
                break;

            case 2:
                foreach ($streamers as $index => $streamer) {
                    $layouts[] = [
                        'streamID' => $streamer['stream_id'],
                        'layout' => [
                            'top' => 0,
                            'left' => $index * ($width / 2),
                            'bottom' => $height,
                            'right' => ($index + 1) * ($width / 2)
                        ]
                    ];
                }
                break;

            case 3:
                // Main streamer takes top half
                $layouts[] = [
                    'streamID' => $streamers[0]['stream_id'],
                    'layout' => [
                        'top' => 0,
                        'left' => 0,
                        'bottom' => $height / 2,
                        'right' => $width
                    ]
                ];
                // Two streamers share bottom half
                for ($i = 1; $i < 3; $i++) {
                    $layouts[] = [
                        'streamID' => $streamers[$i]['stream_id'],
                        'layout' => [
                            'top' => $height / 2,
                            'left' => ($i - 1) * ($width / 2),
                            'bottom' => $height,
                            'right' => $i * ($width / 2)
                        ]
                    ];
                }
                break;

            case 4:
                // 2x2 grid
                foreach ($streamers as $index => $streamer) {
                    $row = floor($index / 2);
                    $col = $index % 2;
                    $layouts[] = [
                        'streamID' => $streamer['stream_id'],
                        'layout' => [
                            'top' => $row * ($height / 2),
                            'left' => $col * ($width / 2),
                            'bottom' => ($row + 1) * ($height / 2),
                            'right' => ($col + 1) * ($width / 2)
                        ]
                    ];
                }
                break;
        }

        return $layouts;
    }

    /**
     * Start a new mixer session
     * Note: Since Zego's "Multiple hosts (Stream mixing)" is activated,
     * mixing happens automatically when multiple users publish in the same room.
     * The mixed output is available on the same room/channel ID.
     */
    public function startMixer(array $streamers): array
    {
        // Zego automatically mixes streams when Multiple hosts is activated
        // The mixed stream is available on the main room/channel ID
        // No additional API call is needed

        $mainStreamId = $streamers[0]['stream_id'] ?? '';
        $mixedStreamId = $mainStreamId; // Same as room ID
        $taskId = 'auto_mixer_' . $mainStreamId;

        try {
            // Save mixer session to database (even though Zego handles it automatically)
            $session = $this->saveMixerSession([
                'task_id' => $taskId,
                'mixed_stream_id' => $mixedStreamId,
                'mixed_stream_url' => '', // No CDN URL needed for RTC mode
                'layout_type' => $this->calculateLayoutType(count($streamers)),
                'config' => ['auto_mixing' => true],
                'streamers' => $streamers
            ]);

            // Log the action
            $this->logMixerAction($session->id, 'start', ['auto_mixing' => true], ['status' => 'automatic'], 200);

            Log::info('🎮 ZEGO MIXER: Automatic mixing active for room', [
                'room_id' => $mainStreamId,
                'streamers_count' => count($streamers),
                'note' => 'Zego Multiple hosts feature handles mixing automatically'
            ]);

            return [
                'success' => true,
                'session_id' => $session->id,
                'task_id' => $taskId,
                'mixed_stream_id' => $mixedStreamId,
                'mixed_stream_url' => '',
                'note' => 'Zego auto-mixing is active. Viewers should play the main room stream.'
            ];

        } catch (Exception $e) {
            Log::error('Mixer session save error', [
                'error' => $e->getMessage(),
                'streamers' => $streamers
            ]);

            throw $e;
        }
    }

    /**
     * Update an existing mixer session
     */
    public function updateMixer(string $taskId, array $streamers): array
    {
        if (empty($streamers)) {
            return $this->stopMixer($taskId);
        }

        $session = MixerSession::where('task_id', $taskId)->firstOrFail();

        $payload = [
            'taskID' => $taskId,
            'inputList' => $this->generateInputLayout($streamers)
        ];

        try {
            $response = $this->sendMixerRequest('update', $payload, $taskId);

            if ($response->successful()) {
                $responseData = $response->json();

                // Update session in database
                $this->updateMixerSession($session, $streamers);

                // Log the action
                $this->logMixerAction($session->id, 'update', $payload, $responseData, 200);

                return [
                    'success' => true,
                    'session_id' => $session->id
                ];
            }

            throw new Exception('Mixer update failed: ' . $response->body());
        } catch (Exception $e) {
            Log::error('Mixer update error', [
                'error' => $e->getMessage(),
                'task_id' => $taskId,
                'streamers' => $streamers
            ]);

            // Log the error
            $this->logMixerAction($session->id, 'update', $payload, null, 500, $e->getMessage());

            // If update fails due to task not found, restart the mixer
            if (str_contains($e->getMessage(), 'task not found')) {
                return $this->startMixer($streamers);
            }

            throw $e;
        }
    }

    /**
     * Stop a mixer session
     */
    public function stopMixer(string $taskId): array
    {
        $session = MixerSession::where('task_id', $taskId)->firstOrFail();

        $payload = [
            'taskID' => $taskId
        ];

        try {
            $response = $this->sendMixerRequest('stop', $payload, $taskId);

            if ($response->successful()) {
                $responseData = $response->json();

                // Update session status
                $session->update(['status' => 'stopped']);

                // Mark all participants as left
                MixerParticipant::where('mixer_session_id', $session->id)
                    ->whereNull('left_at')
                    ->update(['left_at' => now()]);

                // Log the action
                $this->logMixerAction($session->id, 'stop', $payload, $responseData, 200);

                return [
                    'success' => true,
                    'session_id' => $session->id
                ];
            }

            throw new Exception('Mixer stop failed: ' . $response->body());
        } catch (Exception $e) {
            Log::error('Mixer stop error', [
                'error' => $e->getMessage(),
                'task_id' => $taskId
            ]);

            // Log the error
            $this->logMixerAction($session->id, 'stop', $payload, null, 500, $e->getMessage());

            throw $e;
        }
    }

    /**
     * Handle streamer joining
     */
    public function handleStreamerJoin(string $streamId, string $userId, string $chatRoomId): array
    {
        // Check for active mixer sessions
        $activeSession = MixerSession::where('status', 'active')->first();

        if ($activeSession) {
            // Get current participants
            $participants = MixerParticipant::where('mixer_session_id', $activeSession->id)
                ->whereNull('left_at')
                ->get();

            if ($participants->count() < 4) {
                // Add new participant
                $streamers = $participants->map(fn($p) => [
                    'stream_id' => $p->stream_id,
                    'user_id' => $p->user_id,
                    'chat_room_id' => $p->chat_room_id
                ])->toArray();

                $streamers[] = [
                    'stream_id' => $streamId,
                    'user_id' => $userId,
                    'chat_room_id' => $chatRoomId
                ];

                return $this->updateMixer($activeSession->task_id, $streamers);
            }
        } else {
            // Start new mixer with single streamer
            return $this->startMixer([[
                'stream_id' => $streamId,
                'user_id' => $userId,
                'chat_room_id' => $chatRoomId
            ]]);
        }

        return ['success' => false, 'message' => 'Maximum streamers reached'];
    }

    /**
     * Handle streamer leaving
     */
    public function handleStreamerLeave(string $streamId): array
    {
        $participant = MixerParticipant::where('stream_id', $streamId)
            ->whereNull('left_at')
            ->first();

        if (!$participant) {
            return ['success' => false, 'message' => 'Participant not found'];
        }

        $session = MixerSession::find($participant->mixer_session_id);

        if (!$session || $session->status !== 'active') {
            return ['success' => false, 'message' => 'Session not active'];
        }

        // Mark participant as left
        $participant->update(['left_at' => now()]);

        // Get remaining participants
        $remaining = MixerParticipant::where('mixer_session_id', $session->id)
            ->whereNull('left_at')
            ->get();

        if ($remaining->isEmpty()) {
            return $this->stopMixer($session->task_id);
        }

        $streamers = $remaining->map(fn($p) => [
            'stream_id' => $p->stream_id,
            'user_id' => $p->user_id,
            'chat_room_id' => $p->chat_room_id
        ])->toArray();

        return $this->updateMixer($session->task_id, $streamers);
    }

    /**
     * Send HTTP request to ZEGO Mixer API
     */
    private function sendMixerRequest(string $action, array $payload, string $taskId)
    {
        $url = $this->apiUrl . '/mixer/' . $action;

        return Http::withHeaders([
            'AppId' => $this->appId,
            'Signature' => $this->generateSignature($taskId),
            'Content-Type' => 'application/json'
        ])->post($url, $payload);
    }

    /**
     * Save mixer session to database
     */
    private function saveMixerSession(array $data): MixerSession
    {
        $session = MixerSession::create([
            'id' => Str::uuid(),
            'task_id' => $data['task_id'],
            'mixed_stream_id' => $data['mixed_stream_id'],
            'mixed_stream_url' => $data['mixed_stream_url'],
            'layout_type' => $data['layout_type'],
            'config' => $data['config'],
            'status' => 'active'
        ]);

        // Save participants
        foreach ($data['streamers'] as $index => $streamer) {
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

        // Update layout type
        $session->update([
            'layout_type' => $this->calculateLayoutType(count($streamers)),
            'status' => 'active'
        ]);
    }

    /**
     * Log mixer action for debugging
     */
    private function logMixerAction(
        string $sessionId,
        string $action,
        ?array $request,
        ?array $response,
        ?int $statusCode,
        ?string $error = null
    ): void {
        MixerLog::create([
            'id' => Str::uuid(),
            'mixer_session_id' => $sessionId,
            'action' => $action,
            'request_payload' => $request,
            'response_payload' => $response,
            'status_code' => $statusCode,
            'error_message' => $error
        ]);
    }

    /**
     * Check if we should retry the request
     */
    private function shouldRetry(Exception $e): bool
    {
        return str_contains($e->getMessage(), 'ECONNRESET') ||
               str_contains($e->getMessage(), 'timeout');
    }

    /**
     * Get mixer info for a stream
     */
    public function getMixerInfoForStream(string $streamId): ?array
    {
        $participant = MixerParticipant::where('stream_id', $streamId)
            ->whereNull('left_at')
            ->with(['mixerSession', 'mixerSession.participants' => function($query) {
                $query->whereNull('left_at')->with('user');
            }])
            ->first();

        if (!$participant) {
            return null;
        }

        $session = $participant->mixerSession;

        return [
            'session_id' => $session->id,
            'task_id' => $session->task_id,
            'mixed_stream_id' => $session->mixed_stream_id,
            'mixed_stream_url' => $session->mixed_stream_url,
            'layout_type' => $session->layout_type,
            'participants' => $session->participants->map(fn($p) => [
                'stream_id' => $p->stream_id,
                'user_id' => $p->user_id,
                'chat_room_id' => $p->chat_room_id,
                'position' => $p->position,
                'username' => $p->user->username ?? 'Unknown',
                'avatar' => $p->user->avatar ?? null
            ])
        ];
    }
}