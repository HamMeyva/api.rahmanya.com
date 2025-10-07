<?php

namespace App\Observers\Agora;

use App\Models\Agora\AgoraChannel;
use App\Models\User;
use App\Models\Agora\AgoraChannelInvite;

class AgoraChannelInviteObserver
{
    public function creating(AgoraChannelInvite $model)
    {
        if (!$model->agora_channel_data && $model->agora_channel_id) {
            $agoraChannel = AgoraChannel::find($model->agora_channel_id);
            if ($agoraChannel) {
                $user = $agoraChannel->user();

                $model->agora_channel_data = [
                    'id' => $agoraChannel->id ?? null,
                    'language_id' => $agoraChannel->language_id ?? null,
                    'channel_name' => $agoraChannel->channel_name ?? null,
                    'user_id' => $agoraChannel->user_id ?? null,
                    'user_data' => [
                        'id' => $user->id ?? null,
                        'name' => $user->name ?? null,
                        'surname' => $user->surname ?? null,
                        'full_name' => $user->full_name ?? null,
                        'nickname' => $user->nickname ?? null,
                        'email' => $user->email ?? null,
                        'phone' => $user->phone ?? null,
                    ],
                ];
            }
        }

        if (!$model->user_data && $model->user_id) {
            $user = User::find($model->user_id);
            if ($user) {
                $model->user_data = [
                    'id' => $user->id ?? null,
                    'name' => $user->name ?? null,
                    'surname' => $user->surname ?? null,
                    'full_name' => $user->full_name ?? null,
                    'nickname' => $user->nickname ?? null,
                    'email' => $user->email ?? null,
                    'phone' => $user->phone ?? null,
                ];
            }
        }

        if (!$model->invited_user_data && $model->invited_user_id) {
            $invitedUser = User::find($model->invited_user_id);
            if ($invitedUser) {
                $model->invited_user_data = [
                    'id' => $invitedUser->id ?? null,
                    'name' => $invitedUser->name ?? null,
                    'surname' => $invitedUser->surname ?? null,
                    'full_name' => $invitedUser->full_name ?? null,
                    'nickname' => $invitedUser->nickname ?? null,
                    'email' => $invitedUser->email ?? null,
                    'phone' => $invitedUser->phone ?? null,
                ];
            }
        }
    }
}
