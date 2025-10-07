<?php

namespace App\Services;

use App\Models\User;
use App\Models\Agora\AgoraChannel;
use Illuminate\Support\Str;
use App\Services\AgoraTokenService;

class AgoraService
{
    protected $appId;
    protected $appCertificate;
    protected $expirationTimeInSeconds;
    protected $tokenService;

    public function __construct(AgoraTokenService $tokenService)
    {
        $this->appId = config('services.agora.app_id');
        $this->appCertificate = config('services.agora.app_certificate');
        $this->expirationTimeInSeconds = 3600; // 1 saat
        $this->tokenService = $tokenService;
    }

    public function listLiveStreams(User $user)
    {
        try {
            $channels = AgoraChannel::where('is_active', true)->get();
            
            $result = [];
            foreach ($channels as $channel) {
                $userModel = User::find($channel->user_id);
                if ($userModel) {
                    $result[] = [
                        'channelName' => $channel->channel_name ?? '',
                        'userId' => $userModel->id ?? '',
                        'userNickname' => $userModel->nickname ?? '',
                        'userAvatar' => $userModel->avatar ?? '',
                        'createdAt' => $channel->created_at ?? now(),
                    ];
                }
            }
            
            return $result;
        } catch (\Exception $e) {
            \Log::error('Error listing live streams: ' . $e->getMessage());
            return [];
        }
    }


    public function joinStream(User $user, string $channelName)
    {
        try {
            $channel = AgoraChannel::where('channel_name', $channelName)
                ->where('is_online', true)
                ->first();

            if (!$channel) {
                return [
                    'success' => false,
                    'message' => 'Channel not found or not active',
                    'token' => null
                ];
            }

            $token = $this->generateToken($channelName, $user->id);

            return [
                'success' => true,
                'message' => 'Joined stream successfully',
                'token' => $token
            ];
        } catch (\Exception $e) {
            \Log::error('Error joining stream: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to join stream: ' . $e->getMessage(),
                'token' => null
            ];
        }
    }

    protected function generateToken(string $channelName, string $userId): string
    {
        $role = AgoraTokenService::RolePublisher;
        $expireTimeInSeconds = time() + $this->expirationTimeInSeconds;
        $currentTimestamp = time();

        return $this->tokenService->buildTokenWithUid(
            $this->appId,
            $this->appCertificate,
            $channelName,
            $userId,
            $role,
            $expireTimeInSeconds,
            $currentTimestamp
        );
    }
}
