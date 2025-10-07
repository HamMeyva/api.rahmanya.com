<?php

namespace App\Listeners\User;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class MarkUserOnline
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        Log::info('--------------START:MarkUserOnline--------------');
        Log::info($event);
        Log::info('--------------END:MarkUserOnline--------------');
    }
}
