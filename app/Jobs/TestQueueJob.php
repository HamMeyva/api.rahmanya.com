<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TestQueueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The message to be processed.
     *
     * @var string
     */
    protected $message;

    /**
     * Create a new job instance.
     *
     * @param string $message
     * @return void
     */
    public function __construct(string $message)
    {
        $this->message = $message;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('Processing test queue job', [
            'message' => $this->message,
            'queue' => $this->queue,
            'connection' => $this->connection,
            'timestamp' => now()->toDateTimeString()
        ]);
        
        // Simulate some work
        sleep(1);
        
        Log::info('Test queue job completed', [
            'message' => $this->message,
            'queue' => $this->queue,
            'connection' => $this->connection,
            'timestamp' => now()->toDateTimeString()
        ]);
    }
}
