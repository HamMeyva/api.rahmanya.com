<?php

namespace App\Http\Controllers\Api\v1;

use Exception;
use App\Models\Gift;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Services\BunnyCdnService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class GiftBatchUploadController extends Controller
{
    private const BATCH_SIZE = 25;
    private const UPLOAD_TIMEOUT = 60;
    
    public function initiateBatchUpload(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'gift_id' => 'required|exists:gifts,id',
            'total_files' => 'required|integer|min:1|max:300',
            'file_type' => 'required|in:frames,assets'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $uploadId = Str::uuid();
        $totalFiles = $request->input('total_files');
        $batchCount = ceil($totalFiles / self::BATCH_SIZE);
        
        Cache::put("upload_session_{$uploadId}", [
            'gift_id' => $request->input('gift_id'),
            'total_files' => $totalFiles,
            'file_type' => $request->input('file_type'),
            'batch_count' => $batchCount,
            'completed_batches' => 0,
            'uploaded_files' => [],
            'failed_files' => [],
            'status' => 'initialized',
            'created_at' => now()
        ], 3600);

        return response()->json([
            'success' => true,
            'upload_id' => $uploadId,
            'batch_size' => self::BATCH_SIZE,
            'batch_count' => $batchCount,
            'message' => 'Batch upload session initialized'
        ]);
    }

    public function uploadBatch(Request $request): JsonResponse
    {
        set_time_limit(self::UPLOAD_TIMEOUT);
        
        $validator = Validator::make($request->all(), [
            'upload_id' => 'required|string',
            'batch_number' => 'required|integer|min:1',
            'files' => 'required|array|max:' . self::BATCH_SIZE,
            'files.*' => 'required|file|mimes:png,jpg,jpeg,gif,webp|max:10240'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $uploadId = $request->input('upload_id');
        $batchNumber = $request->input('batch_number');
        
        $session = Cache::get("upload_session_{$uploadId}");
        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Upload session not found or expired'
            ], 404);
        }

        try {
            $gift = Gift::findOrFail($session['gift_id']);
            $bunnyCdnService = app(BunnyCdnService::class);
            $uploadedFiles = [];
            $failedFiles = [];

            foreach ($request->file('files') as $index => $file) {
                try {
                    $fileName = $this->generateFileName($file, $session, $batchNumber, $index);
                    $filePath = $this->getUploadPath($gift->id, $session['file_type'], $fileName);
                    
                    $bunnyCdnService->uploadToStorage($filePath, $file->get());
                    
                    $uploadedFiles[] = [
                        'original_name' => $file->getClientOriginalName(),
                        'file_path' => $filePath,
                        'file_size' => $file->getSize()
                    ];
                    
                    usleep(100000); // 0.1 saniye rate limiting
                    
                } catch (Exception $e) {
                    $failedFiles[] = [
                        'original_name' => $file->getClientOriginalName(),
                        'error' => $e->getMessage()
                    ];
                }
            }

            $session['completed_batches']++;
            $session['uploaded_files'] = array_merge($session['uploaded_files'], $uploadedFiles);
            $session['failed_files'] = array_merge($session['failed_files'], $failedFiles);
            $session['status'] = $session['completed_batches'] >= $session['batch_count'] ? 'completed' : 'processing';
            
            Cache::put("upload_session_{$uploadId}", $session, 3600);

            $progress = round(($session['completed_batches'] / $session['batch_count']) * 100, 2);

            return response()->json([
                'success' => true,
                'batch_number' => $batchNumber,
                'uploaded_count' => count($uploadedFiles),
                'failed_count' => count($failedFiles),
                'total_uploaded' => count($session['uploaded_files']),
                'total_failed' => count($session['failed_files']),
                'progress' => $progress,
                'is_complete' => $session['status'] === 'completed',
                'message' => "Batch {$batchNumber} processed successfully"
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function finalizeBatchUpload(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'upload_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $uploadId = $request->input('upload_id');
        $session = Cache::get("upload_session_{$uploadId}");
        
        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Upload session not found'
            ], 404);
        }

        try {
            $gift = Gift::findOrFail($session['gift_id']);
            
            if ($session['file_type'] === 'frames') {
                $framePaths = collect($session['uploaded_files'])->pluck('file_path')->toArray();
                $existingFramePaths = $gift->frame_paths ?? [];
                $allFramePaths = array_merge($existingFramePaths, $framePaths);
                
                $gift->update([
                    'frame_paths' => $allFramePaths,
                    'frame_count' => count($allFramePaths),
                    'is_frame_animation' => true
                ]);
            }

            Cache::forget("upload_session_{$uploadId}");

            return response()->json([
                'success' => true,
                'total_uploaded' => count($session['uploaded_files']),
                'total_failed' => count($session['failed_files']),
                'failed_files' => $session['failed_files'],
                'message' => 'Batch upload completed successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Finalization failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getUploadStatus(Request $request): JsonResponse
    {
        $uploadId = $request->query('upload_id');
        if (!$uploadId) {
            return response()->json([
                'success' => false,
                'message' => 'Upload ID required'
            ], 400);
        }

        $session = Cache::get("upload_session_{$uploadId}");
        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Upload session not found'
            ], 404);
        }

        $progress = $session['batch_count'] > 0 
            ? round(($session['completed_batches'] / $session['batch_count']) * 100, 2)
            : 0;

        return response()->json([
            'success' => true,
            'status' => $session['status'],
            'progress' => $progress,
            'completed_batches' => $session['completed_batches'],
            'total_batches' => $session['batch_count'],
            'uploaded_files_count' => count($session['uploaded_files']),
            'failed_files_count' => count($session['failed_files'])
        ]);
    }

    private function generateFileName($file, array $session, int $batchNumber, int $index): string
    {
        $extension = $file->extension();
        $uuid = Str::uuid();
        
        if ($session['file_type'] === 'frames') {
            $frameNumber = (($batchNumber - 1) * self::BATCH_SIZE) + $index + 1;
            return "{$uuid}_frame_" . str_pad($frameNumber, 3, '0', STR_PAD_LEFT) . ".{$extension}";
        }
        
        return "{$uuid}_asset_{$batchNumber}_{$index}.{$extension}";
    }

    private function getUploadPath(int $giftId, string $fileType, string $fileName): string
    {
        if ($fileType === 'frames') {
            return "gifts/{$giftId}/frames/{$fileName}";
        }
        
        return "gifts/{$giftId}/assets/{$fileName}";
    }
}