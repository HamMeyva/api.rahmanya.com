<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

readonly class AgoraStreamUploadService
{
    public function __construct(
        private ConfigRepository $config
    ) {}

    /**
     * Get Agora Customer ID from configuration
     */
    private function getAgoraCustomerId(): string
    {
        return $this->config->get('services.agora.app_id');
    }

    /**
     * Get Agora Customer Certificate from configuration
     */
    private function getAgoraCustomerCertificate(): string
    {
        return $this->config->get('services.agora.app_certificate');
    }

    /**
     * Get BunnyCDN Library ID from configuration
     */
    private function getBunnyLibraryId(): string
    {
        return $this->config->get('services.bunnycdn.library_id');
    }

    /**
     * Retrieve cloud recording files after stream ends
     */
    public function retrieveRecordedFiles(string $channelName, string $resourceId, string $sid): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode("{$this->agoraCustomerId}:{$this->agoraCustomerCertificate}"),
                'Content-Type' => 'application/json'
            ])->get("https://api.agora.io/v1/cloud-recording/resourceid/{$resourceId}/sid/{$sid}/mode/mix/query");

            if (!$response->successful()) {
                Log::error('Agora Recording Retrieval Failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }

            return $this->extractRecordingFileUrls($response->json());
        } catch (\Exception $e) {
            Log::error('Agora Recording Retrieval Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Download and store recordings to BunnyCDN
     */
    public function storeRecordingsToBunnyCdn(array $fileUrls): array
    {
        $storedFiles = [];

        foreach ($fileUrls as $fileInfo) {
            try {
                // Generate a unique filename
                $tempFilename = Str::uuid() . '_' . $fileInfo['filename'];
                $tempFilePath = Storage::path("temp/{$tempFilename}");

                // Ensure temp directory exists
                Storage::makeDirectory('temp');

                // Download file
                $fileContent = Http::get($fileInfo['url'])->body();
                Storage::put($tempFilePath, $fileContent);

                // Upload to BunnyCDN (using a theoretical BunnyCDN service)
                $uploadResult = $this->uploadToBunnyCdn($tempFilePath, $fileInfo['filename']);

                // Clean up temporary file
                Storage::delete($tempFilePath);

                if ($uploadResult) {
                    $storedFiles[] = [
                        'bunny_guid' => $uploadResult['guid'] ?? null,
                        'original_filename' => $fileInfo['filename'],
                        'upload_status' => 'success'
                    ];
                }
            } catch (\Exception $e) {
                Log::error('Stream Recording Storage Error', [
                    'filename' => $fileInfo['filename'],
                    'message' => $e->getMessage()
                ]);

                $storedFiles[] = [
                    'original_filename' => $fileInfo['filename'],
                    'upload_status' => 'failed',
                    'error' => $e->getMessage()
                ];
            }
        }

        return $storedFiles;
    }

    /**
     * Extract recording file URLs from Agora response
     */
    private function extractRecordingFileUrls(array $recordingData): array
    {
        $fileUrls = [];

        if (isset($recordingData['fileList'])) {
            foreach ($recordingData['fileList'] as $file) {
                $fileUrls[] = [
                    'url' => $file['fileUrl'],
                    'filename' => $file['filename'],
                    'type' => $file['type'] // e.g., 'mix', 'audio', 'video'
                ];
            }
        }

        return $fileUrls;
    }

    /**
     * Stop cloud recording
     */
    public function stopCloudRecording(string $channelName, string $resourceId, string $sid): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode("{$this->agoraCustomerId}:{$this->agoraCustomerCertificate}"),
                'Content-Type' => 'application/json'
            ])->post("https://api.agora.io/v1/cloud-recording/resourceid/{$resourceId}/sid/{$sid}/mode/mix/stop", [
                'cname' => $channelName,
                'uid' => '0',
                'clientRequest' => [
                    'asyncStop' => true
                ]
            ]);

            if (!$response->successful()) {
                Log::error('Agora Stop Recording Failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Agora Stop Recording Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Upload to BunnyCDN (placeholder method)
     * Replace with actual BunnyCDN upload logic
     */
    private function uploadToBunnyCdn(string $filePath, string $originalFilename): ?array
    {
        // This is a placeholder. Implement actual BunnyCDN upload logic
        // You might use a dedicated BunnyCDN service or package
        return [
            'guid' => Str::uuid(),
            'filename' => $originalFilename
        ];
    }
}
