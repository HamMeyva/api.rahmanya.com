<?php

namespace App\Services;

class AgoraTokenService
{
    public const RoleAttendee = 0,
        RolePublisher = 1,
        RoleSubscriber = 2,
        RoleAdmin = 101;

    public function buildTokenWithUid($appId, $appCertificate, $channelName, $uid, $role, $expireTimestamp, $currentTimestamp = 0)
    {
        return $this->buildTokenWithUserAccount($appId, $appCertificate, $channelName, $uid, $role, $expireTimestamp, $currentTimestamp);
    }

    public function buildTokenWithUserAccount($appId, $appCertificate, $channelName, $account, $role, $expireTimestamp, $currentTimestamp = 0)
    {
        $token = $this->buildAccessToken($appId, $appCertificate, $channelName, $account, $role, $expireTimestamp, $currentTimestamp);
        return $token;
    }

    private function buildAccessToken($appId, $appCertificate, $channelName, $account, $role, $expireTimestamp, $currentTimestamp = 0)
    {
        if ($currentTimestamp == 0) {
            $currentTimestamp = time();
        }

        $tokenInfo = [
            'app_id' => $appId,
            'channel_name' => $channelName,
            'account' => $account,
            'role' => $role,
            'privilege' => [
                'expire_timestamp' => $expireTimestamp,
            ],
            'issue_timestamp' => $currentTimestamp,
        ];

        $signature = $this->generateSignature($appCertificate, $tokenInfo);
        $tokenInfo['signature'] = $signature;

        return $this->encodeToken($tokenInfo);
    }

    private function generateSignature($appCertificate, $tokenInfo)
    {
        $content = json_encode($tokenInfo);
        return hash_hmac('sha256', $content, $appCertificate);
    }

    private function encodeToken($tokenInfo)
    {
        return base64_encode(json_encode($tokenInfo));
    }
}