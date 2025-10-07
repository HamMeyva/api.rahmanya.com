<?php

namespace App\Listeners;

use App\Events\UserOffline;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleUserOffline implements ShouldQueue
{
    public function handle(UserOffline $event): void
    {
        $user = User::find($event->userId);
        if ($user) {
            $user->last_seen_at = now();
            $user->save();
        }
    }
}
