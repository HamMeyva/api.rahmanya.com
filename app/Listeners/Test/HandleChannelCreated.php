<?php

namespace App\Listeners\Test;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Laravel\Reverb\Events\ChannelCreated;
use Illuminate\Support\Facades\Log;

class HandleChannelCreated
{
    public function handle(ChannelCreated $event)
    {
        /*try {
            $name = $event->channel->name();
            $connections = $event->channel->connections();

            Log::info('ChannelCreated', [
                'event' => $event,
                'connections' => $connections,
                'name' => $name,
            ]);
        } catch (\Throwable $e) {
            Log::error('ChannelCreated işlenirken hata oluştu: ' . $e->getMessage());
        }*/
    }
}
