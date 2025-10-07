<?php

namespace App\Listeners\Test;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Laravel\Reverb\Events\ChannelRemoved;
use Illuminate\Support\Facades\Log;

class HandleChannelRemoved
{
    public function handle(ChannelRemoved $event)
    {
        /*try {
            $name = $event->channel->name();
            $connections = $event->channel->connections();

            Log::info('ChannelRemoved', [
                'event' => $event,
                'connections' => $connections,
                'name' => $name,
            ]);
        } catch (\Throwable $e) {
            Log::error('ChannelRemoved işlenirken hata oluştu: ' . $e->getMessage());
        }*/
    }
}
