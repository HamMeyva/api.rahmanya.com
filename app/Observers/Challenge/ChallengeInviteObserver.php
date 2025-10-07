<?php

namespace App\Observers\Challenge;

use App\Models\User;
use App\Models\Agora\AgoraChannel;
use App\Models\Challenge\ChallengeInvite;

class ChallengeInviteObserver
{
    public function creating(ChallengeInvite $model)
    {
        //davet geçerlilik süresi ata
        $model->expires_at = now()->addMinutes(3);

        if (!$model->agora_channel_data && $model->agora_channel_id) {
            $agoraChannel = AgoraChannel::find($model->agora_channel_id);
            if ($agoraChannel) {
                $user = User::find($agoraChannel->user_id);
                $model->agora_channel_data = [
                    'id' => $agoraChannel->id ?? null,
                    'language_id' => $agoraChannel->language_id ?? null,
                    'channel_name' => $agoraChannel->channel_name ?? null,
                    'user_id' => $agoraChannel->user_id ?? null,
                    'user_data' => $user ? [
                        'id' => $user->id ?? null,
                        'name' => $user->name ?? null,
                        'surname' => $user->surname ?? null,
                        'full_name' => $user->full_name ?? null,
                        'nickname' => $user->nickname ?? null,
                        'email' => $user->email ?? null,
                        'phone' => $user->phone ?? null,
                    ] : null,
                ];
            }
        }

        if (!$model->sender_user_data && $model->sender_user_id) {
            $senderUser = User::find($model->sender_user_id);
            if ($senderUser) {
                $model->sender_user_data = [
                    'id' => $senderUser->id ?? null,
                    'name' => $senderUser->name ?? null,
                    'surname' => $senderUser->surname ?? null,
                    'full_name' => $senderUser->full_name ?? null,
                    'nickname' => $senderUser->nickname ?? null,
                    'email' => $senderUser->email ?? null,
                    'phone' => $senderUser->phone ?? null,
                ];
            }
        }

        if (!$model->teammate_user_data && $model->teammate_user_id) {
            $teammateUser = User::find($model->teammate_user_id);
            if ($teammateUser) {
                $model->teammate_user_data = [
                    'id' => $teammateUser->id ?? null,
                    'name' => $teammateUser->name ?? null,
                    'surname' => $teammateUser->surname ?? null,
                    'full_name' => $teammateUser->full_name ?? null,
                    'nickname' => $teammateUser->nickname ?? null,
                    'email' => $teammateUser->email ?? null,
                    'phone' => $teammateUser->phone ?? null,
                ];
            }
        }
    }
}
