<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Video;
use Illuminate\Http\Request;
use App\Services\VideoService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Events\VideoProcessingFailed;
use App\Jobs\ProcessVideoStatusUpdate;
use App\Jobs\Video\UpdateVideoDuration;
use App\Events\VideoProcessingCompleted;
use Illuminate\Support\Facades\Validator;
class BunnyWebhookController extends Controller
{
    protected $videoService;

    public function __construct(VideoService $videoService)
    {
        $this->videoService = $videoService;
    }

    /**
     * Handle BunnyCDN webhook for video status changes
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleVideoStatusChange(Request $request)
    {
        // Log the incoming webhook data
        Log::info('BunnyCDN webhook received', [
            'data' => $request->all()
        ]);

        // Validate the webhook payload
        $validator = Validator::make($request->all(), [
            'VideoLibraryId' => 'required|integer',
            'VideoGuid' => 'required|string',
            'Status' => 'required|integer',
        ]);

        if ($validator->fails()) {
            Log::error('Invalid BunnyCDN webhook payload', [
                'errors' => $validator->errors()->toArray(),
                'payload' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid webhook payload',
                'errors' => $validator->errors()
            ], 400);
        }

        // Extract data from the webhook
        $videoGuid = $request->input('VideoGuid');
        $status = $request->input('Status');

        try {
            // Find the video in our database
            $video = Video::where('video_guid', $videoGuid)->first();

            if (!$video) {
                Log::warning('Video not found for BunnyCDN webhook', [
                    'video_guid' => $videoGuid
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Video not found'
                ], 404);
            }

            UpdateVideoDuration::dispatch($video);

            // Process the status update in a queue to avoid timeout
            ProcessVideoStatusUpdate::dispatch($video, $status);

            // For immediate response, also update the video status directly
            $this->updateVideoStatus($video, $status);

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error processing BunnyCDN webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'video_guid' => $videoGuid,
                'status' => $status
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error processing webhook: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update video status based on BunnyCDN status code
     *
     * @param Video $video
     * @param int $bunnyStatus
     * @return void
     */
    protected function updateVideoStatus(Video $video, int $bunnyStatus)
    {
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
        $videoStatus = $statusMap[$bunnyStatus] ?? 'unknown';

        // Update the video status
        $video->status = $videoStatus;

        // Update processing_status for all status types
        // This provides consistent state across all BunnyCDN statuses
        switch ($bunnyStatus) {
            case 3: // Finished
            case 4: // Resolution finished
                $video->processing_status = 'completed';
                
                // Send notification or trigger event for completed video processing
                event(new VideoProcessingCompleted($video));
                break;
                
            case 5: // Failed
            case 8: // Upload failed
                $video->processing_status = 'failed';
                
                // Send notification or trigger event for failed video processing
                event(new VideoProcessingFailed($video));
                break;
                
            case 0: // Queued
                $video->processing_status = 'queued';
                break;
                
            case 1: // Processing
            case 2: // Encoding
            case 6: // Upload started
                $video->processing_status = 'processing';
                break;
                
            case 7: // Upload finished
            case 9: // Captions generated
            case 10: // Metadata generated
                // If it's an auxiliary process and not the main encoding,
                // don't change the processing status unless it was unset
                if (!$video->processing_status) {
                    $video->processing_status = 'processing';
                }
                break;
                
            default:
                $video->processing_status = 'unknown';
        }

        // Save the updated video
        $video->save();

        Log::info('Video status updated from BunnyCDN webhook', [
            'video_id' => $video->id,
            'video_guid' => $video->video_guid,
            'bunny_status' => $bunnyStatus,
            'mapped_status' => $videoStatus,
            'processing_status' => $video->processing_status
        ]);
    }
}
