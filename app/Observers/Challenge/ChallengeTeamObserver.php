<?php

namespace App\Observers\Challenge;

use App\Models\User;
use App\Models\Challenge\ChallengeTeam;
use App\Models\Agora\AgoraChannel;

class ChallengeTeamObserver
{
    public function creating(ChallengeTeam $model)
    {
        if (!$model->total_coins_earned) $model->total_coins_earned = 0;
        if (!$model->win_count) $model->win_count = 0;

        
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

        if (!$model->user_data && $model->user_id) {
            $user = User::find($model->user_id);
            if ($user) {
                $model->user_data = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'surname' => $user->surname,
                    'full_name' => $user->full_name,
                    'nickname' => $user->nickname,
                    'email' => $user->email,
                    'phone' => $user->phone,
                ];
            }
        }
    }
}
