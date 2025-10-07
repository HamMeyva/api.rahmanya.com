<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\v1\Traits\AgoraTokenTrait;
use App\Http\Controllers\Controller;
use App\Models\Agora\AgoraChannel;
use App\Services\AgoraStreamUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AgoraController extends Controller
{
    use AgoraTokenTrait;

    public function listChannels(Request $request): JsonResponse
    {
        $validated = Validator::make($request->all(), [
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1',
            'language' => 'required',
            'is_online' => 'required|boolean',
        ]);

        if ($validated->fails()) {
            return response()->json([
                'success' => false,
                'response' => $validated->errors(),
                'message' => 'Invalid request parameters.'
            ]);
        }

        $channels = AgoraChannel::query()
            ->with('user.primary_team')
            ->where('language', $request->language)
            ->where('is_online', $request->is_online)
            ->paginate($request->perPage, ['*'], 'page', $request->page);

        return response()->json([
            'success' => true,
            'data' => $channels
        ]);
    }

    //@TODO Validate operation by only allowing the user who created the channel to set it offline
    public function setOffline(Request $request): JsonResponse
    {
        $validated = Validator::make($request->all(), [
            'channel_name' => 'required',
        ]);

        if ($validated->fails()) {
            return response()->json([
                'success' => false,
                'response' => $validated->errors(),
                'message' => 'Invalid request parameters.'
            ]);
        }

        $channels = AgoraChannel::query()
            ->where('channel_name', $request->channel_name)
            ->update(['is_online' => false]);

        return response()->json([
            'success' => true,
            'response' => $channels,
            'message' => 'Channel has been set as offline.'
        ]);
    }


    public function handleStreamEnd(Request $request, AgoraStreamUploadService $agoraStreamUploadService)
    {
        $validated = $request->validate([
            'channel_name' => 'required|string',
            'resource_id' => 'required|string',
            'sid' => 'required|string'
        ]);

        try {
            // Stop cloud recording
            $stopResponse = $agoraStreamUploadService->stopCloudRecording(
                $validated['channel_name'],
                $validated['resource_id'],
                $validated['sid']
            );

            // Retrieve recorded files
            $recordedFiles = $agoraStreamUploadService->retrieveRecordedFiles(
                $validated['channel_name'],
                $validated['resource_id'],
                $validated['sid']
            );

            // Store recordings to BunnyCDN
            $storedFiles = $agoraStreamUploadService->storeRecordingsToBunnyCdn($recordedFiles);

            return response()->json([
                'status' => 'success',
                'stored_files' => $storedFiles
            ]);
        } catch (\Exception $e) {
            Log::error('Stream End Processing Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process stream recording'
            ], 500);
        }
    }

}
