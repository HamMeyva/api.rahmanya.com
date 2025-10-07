<?php

namespace App\Observers\Agora;

use App\Models\Gift;
use App\Models\User;
use App\Models\Agora\AgoraChannel;
use App\Models\Agora\AgoraChannelViewer;

class AgoraChannelViewerObserver
{
    public function creating(AgoraChannelViewer $model)
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
                        'id' => $agoraChannel->user_id ?? null,
                        'name' => $user->name ?? null,
                        'surname' => $user->surname ?? null,
                        'nickname' => $user->nickname ?? null,
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
                    'nickname' => $user->nickname ?? null,
                    'primary_team_id' => $user->primary_team_id ?? null,
                ];
            }
        }
    }
}
