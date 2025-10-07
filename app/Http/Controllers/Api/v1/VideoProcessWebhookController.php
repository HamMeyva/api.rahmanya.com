<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\User;
use App\Models\Video;
use Illuminate\Http\Request;
use App\Helpers\CommonHelper;
use App\Services\BunnyCdnService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Notifications\VideoReadyForDownloadNotification;

class VideoProcessWebhookController extends Controller
{
    public function callback(Request $request, BunnyCdnService $bunnyCdnService)
    {
        Log::info('Video process callback:', [
            'success' => $request->success,
            'user_id' => $request->user_id,
        ]);
        $success = $request->input('success');
        if (!$success) {
            Log::error('Video Process Callback: success: false');
            //video işlenme sırasında bir sorun oluştu
            return;
        }
        $processedVideoFile = $request->file('processed_video_file');
        if (!$processedVideoFile && !$request->input('processed_video_url')) {
            Log::error('Video Process Callback: processed_video_file: null and processed_video_url: null');
            //işlemiş video dönmedi ve ya url dönmedi
            return;
        }
        $user = User::find($request->input('user_id'));
        if (!$user) {
            Log::error('Video Process Callback: user not found');
            return;
        }
     
        if ($request->input('processed_video_url')) {
            Log::info('Video Process Callback: processed_video_url: ' . $request->input('processed_video_url'));
            $user->notify(new VideoReadyForDownloadNotification($request->input('processed_video_url')));
        } else {
            $video = Video::find($request->input('video_id'));
            if (!$video) {
                Log::error('Video Process Callback: video not found');
                return;
            }

            $title = time() . '_' . rand(100000000000, 999999999999);

            $thumbnailDuration = $video->temp_thumbnail_duration ?: 0;
            $response = $bunnyCdnService->createVideo($title, $user->collection_uuid, $thumbnailDuration);
            $guid = $response['guid'] ?? null;

            $bunnyCdnService->uploadVideo($guid, $processedVideoFile);

            if ($video->temp_thumbnail_image && file_exists(storage_path('app/public/' . $video->temp_thumbnail_image))) {
                $thumbnailUrl = asset("storage/{$video->temp_thumbnail_image}");
                $bunnyCdnService->setThumbnail($guid, $thumbnailUrl);
                $bunnyVideoData = $bunnyCdnService->getVideo($guid);
                $video->thumbnail_filename = $bunnyVideoData['thumbnailFileName'] ?? null;

                unlink(storage_path('app/public/' . $video->temp_thumbnail_image));
            }

            $video->collection_uuid = $user->collection_uuid;
            $video->video_guid = $guid;
            $video->unset('temp_thumbnail_duration');
            $video->unset('temp_thumbnail_image');
            $video->save();
        }

        return response()->json([
            'success' => true
        ]);
    }
}