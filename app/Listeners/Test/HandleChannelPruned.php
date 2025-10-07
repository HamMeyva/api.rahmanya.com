<?php

namespace App\Listeners\Test;

use Laravel\Reverb\Events\ConnectionPruned;
use Illuminate\Support\Facades\Log;


class HandleChannelPruned
{
    public function handle(ConnectionPruned $event)
    {
        /*try {
            $connection = $event->connection;

            Log::info('ConnectionPruned', [
                'socket_id' => $connection->socketId(),
                'user_id' => $connection->user()?->id,
                'connection_id' => $connection->id(),
            ]);
        } catch (\Throwable $e) {
            Log::error('ConnectionPruned hatası: ' . $e->getMessage());
        }*/
    }
}
