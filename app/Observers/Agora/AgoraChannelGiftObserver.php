<?php

namespace App\Observers\Agora;

use App\Models\Agora\AgoraChannel;
use App\Models\User;
use App\Models\Gift;
use App\Models\Agora\AgoraChannelGift;

class AgoraChannelGiftObserver
{
    public function creating(AgoraChannelGift $model)
    {
        if (!$model->agora_channel_data && $model->agora_channel_id) {
            $agoraChannel = AgoraChannel::find($model->agora_channel_id);
            if ($agoraChannel) {
                $user = User::find($agoraChannel->user_id);
                $model->agora_channel_data = [
                    'id' => $agoraChannel->id,
                    'language_id' => $agoraChannel->language_id,
                    'channel_name' => $agoraChannel->channel_name,
                    'user_id' => $agoraChannel->user_id,
                    'user_data' => $user ? [
                        'id' => $user->id,
                        'name' => $user->name,
                        'surname' => $user->surname,
                        'full_name' => $user->full_name,
                        'nickname' => $user->nickname,
                        'email' => $user->email,
                        'phone' => $user->phone,
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

        if (!$model->recipient_user_data && $model->recipient_user_id) {
            $user = User::find($model->recipient_user_id);
            if ($user) {
                $model->recipient_user_data = [
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

        if (!$model->gift_data && $model->gift_id) {
            $gift = Gift::find($model->gift_id);
            if ($gift) {
                $model->gift_data = [
                    'id' => $gift->id,
                    'image_path' => $gift->image_path,
                    'video_path' => $gift->video_path,
                    'name' => $gift->name,
                    'slug' => $gift->slug,
                    'price' => $gift->price,
                    'is_discount' => $gift->is_discount,
                    'discounted_price' => $gift->discounted_price,
                    'get_final_price' => $gift->get_final_price,
                ];
            }
        }
    }
}
