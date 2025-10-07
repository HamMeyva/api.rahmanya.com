<?php

namespace App\Observers;

use App\Models\UserSessionLog;
use App\Models\User;

class UserSessionLogObserver
{
    public function saving(UserSessionLog $userSessionLog): void
    {
        if ($userSessionLog->start_at && $userSessionLog->end_at) {
            $userSessionLog->duration = (int) $userSessionLog->start_at->diffInSeconds($userSessionLog->end_at);
        }
    }

    public function creating(UserSessionLog $model)
    {
        if (!$model->user_data && $model->user_id) {
            $user = User::find($model->user_id);
            if ($user) {
                $model->user_data = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'surname' => $user->surname,
                    'nickname' => $user->nickname,
                ];
            }
        }
    }
}
