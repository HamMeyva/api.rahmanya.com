<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Video;
use Illuminate\Bus\Queueable;
use App\Services\VideoService;
use Illuminate\Support\Facades\Log;
use App\Events\VideoProcessingFailed;
use Illuminate\Queue\SerializesModels;
use App\Events\VideoProcessingCompleted;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Notifications\VideoStatusUpdatedNotification;

class ProcessVideoStatusUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 60;

    /**
     * The video model instance.
     *
     * @var \App\Models\Video
     */
    protected $video;

    /**
     * The bunny status code.
     *
     * @var int
     */
    protected $bunnyStatus;

    /**
     * Create a new job instance.
     *
     * @param Video $video
     * @param int $bunnyStatus
     * @return void
     */
    public function __construct(Video $video, int $bunnyStatus)
    {
        $this->video = $video;
        $this->bunnyStatus = $bunnyStatus;
        $this->onQueue('video-processing');
    }

    /**
     * Execute the job.
     *
     * @param VideoService $videoService
     * @return void
     */
    public function handle(VideoService $videoService)
    {
        try {
            Log::info('Processing video status update job', [
                'video_id' => $this->video->id,
                'video_guid' => $this->video->video_guid,
                'bunny_status' => $this->bunnyStatus
            ]);

            // Map BunnyCDN status codes to our status values
            $statusMap = [
                0 => 'queued',        // Queued: The video has been queued for encoding
                1 => 'processing',    // Processing: The video has begun processing the preview and format details
                2 => 'encoding',      // Encoding: The video is encoding
                3 => 'finished',      // Finished: The video encoding has finished and the video is fully available
                4 => 'available',     // Resolution finished: One resolution is finished, video is now playable
                5 => 'failed',        // Failed: The video encoding failed
                6 => 'uploading',     // PresignedUploadStarted: A pre-signed upload has been initiated
                7 => 'uploaded',      // PresignedUploadFinished: A pre-signed upload has been completed
                8 => 'upload_failed', // PresignedUploadFailed: A pre-signed upload has failed
                9 => 'captions_ready', // CaptionsGenerated: Automatic captions were generated
                10 => 'metadata_ready' // TitleOrDescriptionGenerated: Automatic generation of title or description completed
            ];

            // Get the mapped status or use 'unknown' if not found
            $videoStatus = $statusMap[$this->bunnyStatus] ?? 'unknown';

            // Update the status in VideoService which handles caching and feeds
            $videoService->updateVideoStatus($this->video->id, $videoStatus);

            // Additional processing that might be intensive
            $this->processAdditionalStatusDetails($videoStatus);

            Log::info('Video status update job processed successfully', [
                'video_id' => $this->video->id,
                'status' => $videoStatus
            ]);
        } catch (\Exception $e) {
            Log::error('Error in ProcessVideoStatusUpdate job', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'video_id' => $this->video->id,
                'bunny_status' => $this->bunnyStatus
            ]);

            throw $e;
        }
    }

    /**
     * Process additional status details based on the video status
     * 
     * @param string $status
     * @return void
     */
    protected function processAdditionalStatusDetails(string $status)
    {
        // Reload the video to get the latest state
        $this->video->refresh();

        // Handle specific status-related actions
        switch ($status) {
            case 'finished':
            case 'available':
                // Additional processing for completed videos
                // For example: generate thumbnails, extract metadata, etc.
                Log::info('Processing completed video', [
                    'video_id' => $this->video->id,
                    'status' => $status
                ]);
                
                // Broadcast that video processing is complete
                if (!$this->video->processing_completed_at) {
                    $this->video->processing_completed_at = now();
                    $this->video->save();
                    event(new VideoProcessingCompleted($this->video));
                    
                    // Broadcast video status update to user channel via Reverb
                    try {
                        $userId = $this->video->user_id;
                        $user = User::find($userId);
                        if ($user) {
                            $user->notify(new VideoStatusUpdatedNotification($this->video->id, $this->video->video_guid, $status));
                        }
                    } catch (\Exception $e) {
                        Log::error('Error broadcasting video status update', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                            'video_id' => $this->video->id
                        ]);
                    }
                }
                break;

            case 'failed':
            case 'upload_failed':
                // Handle failure scenarios
                Log::warning('Video processing failed', [
                    'video_id' => $this->video->id,
                    'video_guid' => $this->video->video_guid,
                    'status' => $status
                ]);
                
                // Broadcast that video processing failed
                if (!$this->video->processing_failed_at) {
                    $this->video->processing_failed_at = now();
                    $this->video->save();
                    event(new VideoProcessingFailed($this->video));
                    
                    // Broadcast video failure status to user channel
                    try {
                        $userId = $this->video->user_id;
                        $user = User::find($userId);
                        if ($user) {
                            $user->notify(new VideoStatusUpdatedNotification($this->video->id, $this->video->video_guid, $status));
                        }
                    } catch (\Exception $e) {
                        Log::error('Error broadcasting video failure status', [
                            'error' => $e->getMessage(),
                            'video_id' => $this->video->id
                        ]);
                    }
                }
                break;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error('ProcessVideoStatusUpdate job failed', [
            'error' => $exception->getMessage(),
            'video_id' => $this->video->id,
            'bunny_status' => $this->bunnyStatus
        ]);
    }
}
