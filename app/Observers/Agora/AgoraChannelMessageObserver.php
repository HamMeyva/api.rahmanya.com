<?php

namespace App\Observers\Agora;

use App\Models\User;
use App\Models\Admin;
use App\Models\Agora\AgoraChannel;
use App\Models\Agora\AgoraChannelMessage;

class AgoraChannelMessageObserver
{
    public function creating(AgoraChannelMessage $model)
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

        if (!$model->admin_data && $model->admin_id) {
            $admin = Admin::find($model->admin_id);
            if ($admin) {
                $model->admin_data = [
                    'first_name' => $admin->first_name ?? null,
                    'last_name' => $admin->last_name ?? null,
                ];
            }
        }
    }
}
