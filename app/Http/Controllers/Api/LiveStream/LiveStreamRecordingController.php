<?php

namespace App\Http\Controllers\Api\LiveStream;

use App\Http\Controllers\Controller;
use App\Models\Agora\AgoraChannel;
use App\Services\AgoraStreamUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class LiveStreamRecordingController extends Controller
{
    /**
     * @var AgoraStreamUploadService
     */
    protected $uploadService;

    /**
     * LiveStreamRecordingController constructor.
     *
     * @param AgoraStreamUploadService $uploadService
     */
    public function __construct(AgoraStreamUploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }

    /**
     * Yayın kaydını başlatır
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function startRecording(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'channel_name' => 'required|string',
            'uid' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $stream = AgoraChannel::where('channel_name', $request->channel_name)->firstOrFail();
            
            // Yetki kontrolü
            if (Auth::id() !== $stream->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $result = $this->uploadService->startCloudRecording(
                $request->channel_name,
                $request->uid
            );

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to start cloud recording', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to start cloud recording: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Yayın kaydını durdurur ve dosyaları sunucuya yükler
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handleStreamEnd(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'channel_name' => 'required|string',
            'resource_id' => 'required|string',
            'sid' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $stream = AgoraChannel::where('channel_name', $request->channel_name)->first();
            
            // Yetki kontrolü - yayıncı veya admin ise
            if ($stream && Auth::id() !== $stream->user_id && !Auth::user()->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Stop cloud recording
            $stopResponse = $this->uploadService->stopCloudRecording(
                $request->channel_name,
                $request->resource_id,
                $request->sid
            );

            // Retrieve recorded files
            $recordedFiles = $this->uploadService->retrieveRecordedFiles(
                $request->channel_name,
                $request->resource_id,
                $request->sid
            );

            // Store recordings to BunnyCDN
            $storedFiles = $this->uploadService->storeRecordingsToBunnyCdn($recordedFiles);

            // Update stream with recording info
            if ($stream) {
                $stream->recording_urls = $storedFiles;
                $stream->has_recording = true;
                $stream->save();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'stop_response' => $stopResponse,
                    'stored_files' => $storedFiles
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Stream End Processing Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process stream recording: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Yayın kayıtlarını listeler
     *
     * @param Request $request
     * @param string $streamId
     * @return JsonResponse
     */
    public function getRecordings(Request $request, string $streamId): JsonResponse
    {
        try {
            $stream = AgoraChannel::findOrFail($streamId);
            
            return response()->json([
                'success' => true,
                'data' => $stream->recording_urls ?? []
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get recordings: ' . $e->getMessage()
            ], 500);
        }
    }
}
