<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class BunnyCdnService
{
    protected string $apiKey;
    protected string $libraryId;
    protected string $cdnUrl;
    protected string $apiUrl;
    protected string $page;
    protected string $perPage;
    protected ImageManager $imageManager;

    // Storage API için gerekli değişkenler
    protected string $storageApiKey;
    protected string $storageZoneName;
    protected string $storageUrl;
    protected string $storageCdnUrl;

    public function __construct()
    {
        // Video API yapılandırması
        $this->apiKey = config('bunnycdn-library.api_key', env('BUNNYCDN_API_KEY', ''));
        $this->libraryId = config('bunnycdn-library.library_id', env('BUNNYCDN_LIBRARY_ID', ''));
        $this->cdnUrl = config('bunnycdn-library.host_name', env('BUNNYCDN_CDN_URL', ''));
        $this->apiUrl = 'https://video.bunnycdn.com/library/' . $this->libraryId;
        $this->page = config('bunnycdn-library.page', '1');
        $this->perPage = config('bunnycdn-library.perPage', '100');

        // Storage API yapılandırması
        $this->storageApiKey = env('BUNNYCDN_STORAGE_API_KEY', config('bunnycdn-storage.api_key', ''));
        $this->storageZoneName = env('BUNNYCDN_STORAGE_ZONE_NAME', config('bunnycdn-storage.zone_name', ''));

        // Get region and base hostname from config
        $region = config('bunnycdn-storage.region', '');
        $baseHostname = config('bunnycdn-storage.base_hostname', 'storage.bunnycdn.com');

        // Build the storage URL with or without region prefix
        $hostname = !empty($region) ? "{$region}.{$baseHostname}" : $baseHostname;

        // This URL is used for API operations (uploading files)
        $this->storageUrl = "https://{$hostname}";

        // Set the CDN URL for serving media files
        // Use the configured CDN URL or fall back to the default pull zone URL
        $this->storageCdnUrl = env('BUNNYCDN_STORAGE_CDN_URL', config('bunnycdn-storage.cdn_url', ''));

        // If no CDN URL is configured, use the pull zone URL format
        if (empty($this->storageCdnUrl)) {
            $this->storageCdnUrl = "https://{$this->storageZoneName}.b-cdn.net";
        }

        // Initialize the ImageManager for image processing
        if (class_exists(ImageManager::class)) {
            $this->imageManager = new ImageManager(new Driver());
        }
    }

    /**
     * Perform an HTTP request to the Bunny CDN API.
     */
    public function makeRequest(string $method, string $endpoint, $body = null, $isFile = false): string
    {
        $url = 'https://video.bunnycdn.com/library/' . $endpoint;

        $requestOptions = [];

        if (!$isFile && !empty($body)) {
            $requestOptions['body'] = $body;
        }
        // Set up headers and body if applicable
        $requestOptions['headers'] = [
            'AccessKey' => $this->apiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'timeout' => 300,
        ];

        // Geliştirme ortamında SSL doğrulamasını devre dışı bırak
        if (app()->environment('local')) {
            $requestOptions['verify'] = false;
        }

        if ($isFile) {
            $requestOptions['body'] = $body;
        }

        try {
            // Perform the request
            $client = new Client();
            $response = $client->request($method, $url, $requestOptions);

            return (string)$response->getBody();
        } catch (Exception $e) {
            Log::error('BunnyCDN API request error: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Create a direct upload URL for client-side uploads
     *
     * @param string $title Video title
     * @param string|null $collectionId Collection ID
     * @return array Upload URL and video ID
     */
    public function createVideoUploadUrl(string $title, ?string $collectionId = null): array
    {
        try {
            $httpOptions = [];

            // Geliştirme ortamında SSL doğrulamasını devre dışı bırak
            if (app()->environment('local')) {
                $httpOptions['verify'] = false;
            }

            $response = Http::withOptions($httpOptions)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'AccessKey' => $this->apiKey
                ])->post($this->apiUrl . '/videos', [
                    'title' => $title,
                    'collectionId' => $collectionId
                ]);

            if ($response->successful()) {
                $data = $response->json();

                // Get the upload URL for the created video
                $videoId = $data['guid'] ?? null;

                if ($videoId) {
                    $uploadUrlResponse = Http::withOptions($httpOptions)
                        ->withHeaders([
                            'Accept' => 'application/json',
                            'Content-Type' => 'application/json',
                            'AccessKey' => $this->apiKey
                        ])->get($this->apiUrl . '/videos/' . $videoId . '/upload');

                    if ($uploadUrlResponse->successful()) {
                        $uploadData = $uploadUrlResponse->json();

                        return [
                            'success' => true,
                            'videoId' => $videoId,
                            'uploadUrl' => $uploadData['uploadUrl'] ?? null,
                            'expiresAt' => $uploadData['expiresTime'] ?? null
                        ];
                    }
                }
            }

            Log::error('Failed to create BunnyCDN upload URL', [
                'response' => $response->json(),
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to create upload URL',
                'error' => $response->json()
            ];
        } catch (Exception $e) {
            Log::error('BunnyCDN upload URL creation error: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error creating upload URL: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Extract video metadata from BunnyCDN
     *
     * @param string $videoId BunnyCDN video ID
     * @return array Video metadata
     */
    public function extractVideoMetadata(string $videoId): array
    {
        try {
            $httpOptions = [];

            // Geliştirme ortamında SSL doğrulamasını devre dışı bırak
            if (app()->environment('local')) {
                $httpOptions['verify'] = false;
            }

            $response = Http::withOptions($httpOptions)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'AccessKey' => $this->apiKey
                ])->get($this->apiUrl . '/videos/' . $videoId);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'title' => $data['title'] ?? '',
                    'length' => $data['length'] ?? 0,
                    'width' => $data['width'] ?? 0,
                    'height' => $data['height'] ?? 0,
                    'framerate' => $data['framerate'] ?? 0,
                    'status' => $data['status'] ?? '',
                    'thumbnailUrl' => $this->getThumbnailUrl($videoId),
                    'originalData' => $data
                ];
            }

            Log::error('Failed to extract BunnyCDN video metadata', [
                'videoId' => $videoId,
                'response' => $response->json(),
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to extract video metadata',
                'error' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('BunnyCDN metadata extraction error: ' . $e->getMessage(), [
                'videoId' => $videoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error extracting video metadata: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Fetch video collections from the library.
     */
    public function getVideoCollections(): string
    {
        $queryParams = http_build_query([
            'page' => $this->page,
            'itemsPerPage' => $this->perPage,
            'orderBy' => 'date',
        ]);

        return $this->makeRequest('GET', "{$this->libraryId}/collections?$queryParams");
    }

    /**
     * List videos from the library.
     */
    public function listVideos(): string
    {
        $queryParams = http_build_query([
            'page' => $this->page,
            'itemsPerPage' => $this->perPage,
            'orderBy' => 'date',
            'includeThumbnails' => true,
        ]);

        return $this->makeRequest('GET', "{$this->libraryId}/videos?$queryParams");
    }

    /**
     * Fetch play data for a specific video.
     */
    public function getVideoPlayData(string $videoId): string
    {
        return $this->makeRequest('GET', "{$this->libraryId}/videos/{$videoId}/play");
    }

    /**
     * Create collection id for specific user.
     */
    public function createCollection($dataSet): string
    {
        // Ensure dataSet is properly formatted
        $collectionData = ['name' => (string)$dataSet];

        // Log the collection creation request
        Log::info('Creating BunnyCDN collection', [
            'collection_name' => (string)$dataSet
        ]);

        // Properly encode JSON with correct flags
        $body = json_encode($collectionData, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);

        $result = $this->makeRequest('POST', "{$this->libraryId}/collections", $body);

        // Log the response
        Log::info('BunnyCDN collection creation response', [
            'response' => $result
        ]);

        $resultData = json_decode($result, true, 512, JSON_THROW_ON_ERROR);

        if (!isset($resultData['guid'])) {
            Log::error('Failed to create BunnyCDN collection - no GUID returned', [
                'response' => $result
            ]);
            throw new \Exception('Failed to create BunnyCDN collection: ' . ($resultData['message'] ?? 'Unknown error'));
        }

        return $resultData['guid'];
    }

    /**
     * Upload media content directly to BunnyCDN
     *
     * @param string $mediaData Binary media data
     * @param string $guid Unique identifier for the media
     * @param string $extension File extension (jpg, mp4, etc.)
     * @param string|null $userId User ID for organizing files (optional)
     * @param string|null $contentType Type of content (story, stream, etc.)
     * @return array Upload result with URLs
     * @throws \Exception
     */
    public function uploadMedia(string $mediaData, string $guid, string $extension = 'jpg', ?string $userId = null, ?string $contentType = null): array
    {
        try {
            // Determine media type based on extension
            $mediaType = ($extension === 'mp4') ? 'video' : 'image';

            // Determine content folder based on contentType parameter
            $contentFolder = 'stories'; // Default folder
            if ($contentType === 'stream') {
                $contentFolder = 'streams';
            } elseif ($contentType === 'avatar') {
                $contentFolder = 'users';
            }

            // Create paths for media and thumbnail
            // If userId is provided, organize files by user: {contentFolder}/{userId}/{guid}/
            // Otherwise use the original structure: {contentFolder}/{guid}/
            $basePath = $contentFolder . '/';

            // Always include userId in the path if available
            if (!empty($userId)) {
                $basePath .= $userId . '/';
            }

            // For avatars, add subfolder
            if ($contentType === 'avatar') {
                $basePath .= 'avatar/';
            }

            $basePath .= $guid . '/';

            // Determine file name based on content type
            $fileName = ($contentType === 'stream') ? 'thumbnail' : ($contentType === 'avatar' ? 'avatar' : 'story');
            $mediaPath = $basePath . $fileName . '.' . $extension;
            $thumbnailPath = $basePath . 'thumbnail.jpg';

            // Log the paths being used
            Log::info('Creating media paths for BunnyCDN upload', [
                'guid' => $guid,
                'user_id' => $userId,
                'media_path' => $mediaPath,
                'thumbnail_path' => $thumbnailPath,
                'storage_url' => $this->storageUrl,
                'cdn_url' => $this->storageCdnUrl
            ]);

            // Upload the media file
            $uploadSuccess = $this->uploadToStorage($mediaPath, $mediaData);

            if (!$uploadSuccess) {
                throw new \Exception('Failed to upload media to BunnyCDN Storage');
            }

            // For images, create a thumbnail
            if ($mediaType === 'image' && class_exists(ImageManager::class)) {
                // Save to temporary file
                $tempPath = sys_get_temp_dir() . '/' . $guid . '_temp.' . $extension;
                file_put_contents($tempPath, $mediaData);

                // Create thumbnail - preserving aspect ratio without white background
                $image = $this->imageManager->read($tempPath);

                // Get original dimensions
                $width = $image->width();
                $height = $image->height();

                // Calculate aspect ratio
                $aspectRatio = $width / $height;

                // Resize with crop to maintain aspect ratio without stretching or adding background
                if ($aspectRatio > 1) {
                    // Landscape image: resize height to 300px and crop width
                    $newHeight = 300;
                    $newWidth = intval($newHeight * $aspectRatio);
                    $image->resize($newWidth, $newHeight);
                    $image->crop(300, 300, intval(($newWidth - 300) / 2), 0);
                } else {
                    // Portrait image: resize width to 300px and crop height
                    $newWidth = 300;
                    $newHeight = intval($newWidth / $aspectRatio);
                    $image->resize($newWidth, $newHeight);
                    $image->crop(300, 300, 0, intval(($newHeight - 300) / 2));
                }

                // Save thumbnail to temp file
                $tempThumbPath = sys_get_temp_dir() . '/' . $guid . '_thumbnail.jpg';
                $image->save($tempThumbPath, 80);

                // Upload thumbnail
                $this->uploadToStorage($thumbnailPath, file_get_contents($tempThumbPath));

                // Clean up temp files
                @unlink($tempPath);
                @unlink($tempThumbPath);
            } else if ($mediaType === 'video') {
                // For videos, we'll use the first frame as thumbnail once processing is complete
                // This is handled by the BunnyWebhookController when video processing is done
            } else {
                // If Intervention Image is not available, use the original as thumbnail
                $this->uploadToStorage($thumbnailPath, $mediaData);
            }

            return [
                'guid' => $guid,
                'url' => $this->getStorageUrl($mediaPath),
                'thumbnail_url' => $this->getStorageUrl($thumbnailPath),
                'media_type' => $mediaType
            ];
        } catch (\Exception $e) {
            Log::error('Error uploading media to BunnyCDN: ' . $e->getMessage(), [
                'guid' => $guid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Upload a file to BunnyCDN Storage with retry mechanism for 401 errors
     *
     * @param string $path Path in storage zone (without leading slash)
     * @param string $content File content
     * @param int $maxRetries Maximum number of retries for 401 errors
     * @return bool Success status
     */
    public function uploadToStorage(string $path, string $content, int $maxRetries = 3): bool
    {
        $attempt = 0;
        
        while ($attempt <= $maxRetries) {
            try {
                $result = $this->attemptUploadToStorage($path, $content, $attempt);
                
                if ($result === true) {
                    return true;
                }
                
                // Eğer 401 hatası değilse tekrar deneme
                if ($result !== 401) {
                    return false;
                }
                
                $attempt++;
                
                if ($attempt <= $maxRetries) {
                    // Exponential backoff: 1s, 2s, 4s
                    $delay = pow(2, $attempt - 1);
                    Log::warning("BunnyCDN 401 error, retrying in {$delay} seconds (attempt {$attempt}/{$maxRetries})", [
                        'path' => $path,
                        'attempt' => $attempt
                    ]);
                    sleep($delay);
                }
            } catch (Exception $e) {
                Log::error('Error in uploadToStorage retry loop: ' . $e->getMessage(), [
                    'path' => $path,
                    'attempt' => $attempt,
                    'error' => $e->getMessage()
                ]);
                return false;
            }
        }
        
        Log::error('Failed to upload after all retries', [
            'path' => $path,
            'max_retries' => $maxRetries
        ]);
        
        return false;
    }

    /**
     * Attempt to upload a file to BunnyCDN Storage (single attempt)
     *
     * @param string $path Path in storage zone (without leading slash)
     * @param string $content File content
     * @param int $attemptNumber Current attempt number for logging
     * @return bool|int Success status (true) or HTTP error code (401, etc.)
     */
    private function attemptUploadToStorage(string $path, string $content, int $attemptNumber = 0): bool|int
    {
        try {
            // Refresh config values in case they weren't loaded properly
            if (empty($this->storageApiKey)) {
                $this->storageApiKey = env('BUNNYCDN_STORAGE_API_KEY');
            }
            if (empty($this->storageZoneName)) {
                $this->storageZoneName = env('BUNNYCDN_STORAGE_ZONE_NAME');
            }
            
            // Validate storage configuration with detailed logging
            if (empty($this->storageApiKey) || empty($this->storageZoneName) || empty($this->storageUrl)) {
                Log::error('BunnyCDN Storage configuration is incomplete', [
                    'storage_api_key_set' => !empty($this->storageApiKey),
                    'storage_zone_name_set' => !empty($this->storageZoneName),
                    'storage_url_set' => !empty($this->storageUrl),
                    'storage_api_key_length' => empty($this->storageApiKey) ? 0 : strlen($this->storageApiKey),
                    'storage_api_key_first_chars' => empty($this->storageApiKey) ? 'N/A' : substr($this->storageApiKey, 0, 10) . '...',
                    'storage_zone_name' => $this->storageZoneName,
                    'storage_url' => $this->storageUrl,
                    'env_storage_api_key_set' => !empty(env('BUNNYCDN_STORAGE_API_KEY')),
                    'env_storage_zone_name_set' => !empty(env('BUNNYCDN_STORAGE_ZONE_NAME')),
                    'env_storage_cdn_url_set' => !empty(env('BUNNYCDN_STORAGE_CDN_URL'))
                ]);
                return false;
            }

            // Debug API key format
            if ($attemptNumber === 0) {
                Log::info('BunnyCDN API key debug info', [
                    'api_key_length' => strlen($this->storageApiKey),
                    'api_key_starts_with' => substr($this->storageApiKey, 0, 10) . '...',
                    'api_key_ends_with' => '...' . substr($this->storageApiKey, -10),
                    'zone_name' => $this->storageZoneName,
                    'storage_url' => $this->storageUrl,
                    'has_dashes' => strpos($this->storageApiKey, '-') !== false,
                    'api_key_format_check' => preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}$/', $this->storageApiKey)
                ]);
            }

            // Log content size for debugging
            $contentSize = strlen($content);
            Log::info('Preparing to upload content to BunnyCDN Storage (attempt ' . ($attemptNumber + 1) . ')', [
                'path' => $path,
                'content_size' => $contentSize
            ]);

            if ($contentSize === 0) {
                Log::error('Cannot upload empty content to BunnyCDN Storage', [
                    'path' => $path
                ]);
                return false;
            }

            // Create a temporary file with the content
            $tempFile = tempnam(sys_get_temp_dir(), 'bunny_upload_');
            $bytesWritten = file_put_contents($tempFile, $content);

            if ($bytesWritten === false || $bytesWritten === 0) {
                Log::error('Failed to write content to temporary file', [
                    'path' => $path,
                    'temp_file' => $tempFile
                ]);
                return false;
            }

            // Set up cURL request
            $ch = curl_init();
            // Include the storage zone name in the URL
            $url = $this->storageUrl . '/' . $this->storageZoneName . '/' . $path;

            // Log the upload URL for debugging
            Log::info('Uploading to BunnyCDN Storage', [
                'storage_url' => $this->storageUrl,
                'storage_zone' => $this->storageZoneName,
                'path' => $path,
                'full_url' => $url,
                'content_size' => $contentSize,
                'temp_file_size' => filesize($tempFile)
            ]);

            // Alternatif yöntem: file_get_contents ve HTTP isteği kullanarak yükleme
            $fileContent = file_get_contents($tempFile);
            if ($fileContent === false) {
                Log::error('Failed to read temporary file for upload', [
                    'temp_file' => $tempFile
                ]);
                @unlink($tempFile);
                return false;
            }

            // Debug headers for first attempt
            if ($attemptNumber === 0) {
                Log::info('BunnyCDN upload headers debug', [
                    'content_type' => 'application/octet-stream',
                    'access_key_length' => strlen($this->storageApiKey),
                    'full_url' => $url
                ]);
            }

            // Test different API key formats if this is a retry
            $testApiKey = $this->storageApiKey;
            if ($attemptNumber > 0) {
                // BunnyCDN bazen farklı format bekliyor
                if (strpos($this->storageApiKey, 'FTP') === false) {
                    $testApiKey = 'FTP-' . $this->storageApiKey;
                    Log::info('Trying API key with FTP prefix', ['attempt' => $attemptNumber]);
                }
            }

            // HTTP bağlam seçeneklerini ayarla  
            $headers = [
                'Content-Type: application/octet-stream',
                'AccessKey: ' . $testApiKey,
                'User-Agent: shoot90-backend/1.0'
            ];
            
            $options = [
                'http' => [
                    'method' => 'PUT',
                    'header' => implode("\r\n", $headers),
                    'content' => $fileContent,
                    'timeout' => 60,
                    'ignore_errors' => true
                ]
            ];

            // SSL doğrulamasını devre dışı bırak (geliştirme ortamında)
            if (app()->environment('local')) {
                $options['ssl'] = [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ];
            }

            // HTTP bağlamını oluştur
            $context = stream_context_create($options);

            // İsteği gönder
            Log::info('Sending HTTP request to BunnyCDN', [
                'url' => $url,
                'method' => 'PUT',
                'content_length' => strlen($fileContent)
            ]);

            // İsteği gönder ve yanıtı al
            $result = @file_get_contents($url, false, $context);

            // HTTP yanıt kodunu al
            $httpCode = 0;
            if (isset($http_response_header)) {
                foreach ($http_response_header as $header) {
                    if (preg_match('#HTTP/[0-9\.]+\s+([0-9]+)#', $header, $matches)) {
                        $httpCode = intval($matches[1]);
                        break;
                    }
                }
            }

            // Clean up the temporary file
            @unlink($tempFile);

            // Başarılı yanıt kodları: 200 OK veya 201 Created
            if ($httpCode !== 200 && $httpCode !== 201) {
                Log::error('Failed to upload file to BunnyCDN Storage using file_get_contents', [
                    'path' => $path,
                    'status' => $httpCode,
                    'response' => $result,
                    'http_response_header' => $http_response_header ?? 'No response headers'
                ]);

                // 401 error'ı retry için return et
                if ($httpCode === 401) {
                    return 401;
                }

                // Diğer hatalar için curl ile tekrar deneyelim
                $curlResult = $this->uploadToStorageWithCurl($url, $content, $path, $testApiKey);
                return $curlResult ? true : $httpCode;
            }

            Log::info('Successfully uploaded file to BunnyCDN Storage', [
                'path' => $path,
                'status' => $httpCode,
                'content_size' => $contentSize
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Error uploading file to BunnyCDN Storage: ' . $e->getMessage(), [
                'path' => $path,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Upload a file to BunnyCDN Storage using cURL as a fallback method
     *
     * @param string $url Full URL to upload to
     * @param string $content File content
     * @param string $path Path in storage zone (for logging)
     * @param string $apiKey API key to use
     * @return bool|int Success status (true) or HTTP error code
     */
    private function uploadToStorageWithCurl(string $url, string $content, string $path, string $apiKey = null): bool|int
    {
        try {
            $useApiKey = $apiKey ?: $this->storageApiKey;
            // Create a temporary file with the content
            $tempFile = tempnam(sys_get_temp_dir(), 'bunny_curl_');
            $bytesWritten = file_put_contents($tempFile, $content);

            if ($bytesWritten === false || $bytesWritten === 0) {
                Log::error('Failed to write content to temporary file for cURL fallback', [
                    'path' => $path,
                    'temp_file' => $tempFile
                ]);
                return false;
            }

            $fileHandle = fopen($tempFile, 'r');
            if (!$fileHandle) {
                Log::error('Failed to open temporary file for reading (cURL fallback)', [
                    'temp_file' => $tempFile
                ]);
                @unlink($tempFile);
                return false;
            }

            // Set up cURL request
            $ch = curl_init();

            Log::info('Trying cURL fallback for BunnyCDN upload', [
                'url' => $url,
                'file_size' => filesize($tempFile)
            ]);

            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_PUT => true,
                CURLOPT_INFILE => $fileHandle,
                CURLOPT_INFILESIZE => filesize($tempFile),
                CURLOPT_HTTPHEADER => [
                    "AccessKey: {$useApiKey}",
                    'Content-Type: application/octet-stream'
                ],
                CURLOPT_TIMEOUT => 60,
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_VERBOSE => true
            ]);

            // Geliştirme ortamında SSL doğrulamasını devre dışı bırak
            if (app()->environment('local')) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            }

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);

            // Close the file handle
            fclose($fileHandle);

            // Clean up the temporary file
            @unlink($tempFile);

            if ($httpCode !== 200 && $httpCode !== 201) {
                Log::error('Failed to upload file to BunnyCDN Storage using cURL fallback', [
                    'path' => $path,
                    'status' => $httpCode,
                    'response' => $response,
                    'error' => $error,
                    'errno' => $errno
                ]);
                return $httpCode; // Return the actual HTTP error code
            }

            Log::info('Successfully uploaded file to BunnyCDN Storage using cURL fallback', [
                'path' => $path,
                'status' => $httpCode
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Error in cURL fallback upload to BunnyCDN: ' . $e->getMessage(), [
                'path' => $path,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * @link https://docs.bunny.net/reference/video_createvideo
     */
    public function createVideo(string $title, string $collectionId, int $thumbnailTime = 0): array
    {
        $client = new Client();

        $url = "https://video.bunnycdn.com/library/{$this->libraryId}/videos";

        try {
            $response = $client->request('POST', $url, [
                'headers' => [
                    'AccessKey' => $this->apiKey,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode([
                    'title' => $title,
                    'collectionId' => $collectionId,
                    'thumbnailTime' => $thumbnailTime * 1000,
                ]),
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            Log::info('BunnyCDN video created successfully.', [
                'video' => $responseData
            ]);

            return $responseData;
        } catch (Exception $e) {
            Log::error('BunnyCDN video creation failed.', [
                'error' => $e->getMessage(),
            ]);

            throw new Exception("Video oluşturulamadı: " . $e->getMessage());
        }
    }

    /**
     * @link https://docs.bunny.net/reference/video_uploadvideo
     */
    public function uploadVideo(string $videoGuid, UploadedFile $file)
    {
        $client = new Client();

        $url = "https://video.bunnycdn.com/library/{$this->libraryId}/videos/{$videoGuid}";

        try {
            $response = $client->request('PUT', $url, [
                'headers' => [
                    'AccessKey' => $this->apiKey,
                    'Content-Type' => 'application/octet-stream',
                ],
                'body' => fopen($file->getPathname(), 'r'),
            ]);

            Log::info('BunnyCDN video upload success.', [
                'videoGuid' => $videoGuid,
                'statusCode' => $response->getStatusCode(),
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (Exception $e) {
            Log::error('BunnyCDN video upload failed.', [
                'videoGuid' => $videoGuid,
                'error' => $e->getMessage(),
            ]);

            throw new Exception("Video yüklenemedi: " . $e->getMessage());
        }
    }

    /**
     * @link https://docs.bunny.net/reference/video_setthumbnail
     */
    public function setThumbnail(string $videoGuid, string $thumbnailUrl)
    {
        $client = new Client();

        $url = "https://video.bunnycdn.com/library/{$this->libraryId}/videos/{$videoGuid}/thumbnail";

        try {
            $response = $client->request('POST', $url, [
                'headers' => [
                    'AccessKey' => $this->apiKey,
                    'accept' => 'application/json',
                ],
                'query' => [
                    'thumbnailUrl' => "https://e7.pngegg.com/pngimages/719/649/png-clipart-laravel-software-framework-web-framework-php-zend-framework-framework-icon-angle-text.png" //$thumbnailUrl,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (Exception $e) {
            Log::error('BunnyCDN video upload failed.', [
                'videoGuid' => $videoGuid,
                'error' => $e->getMessage(),
            ]);

            throw new Exception("Video yüklenemedi: " . $e->getMessage());
        }
    }

    /**
     * @link https://docs.bunny.net/reference/video_getvideo
     */
    public function getVideo(string $videoGuid)
    {
        $client = new Client();

        $url = "https://video.bunnycdn.com/library/{$this->libraryId}/videos/{$videoGuid}";

        try {
            $response = $client->request('GET', $url, [
                'headers' => [
                    'AccessKey' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ]
            ]);

            Log::info('BunnyCDN getVideo success.', [
                'video_guid' => $videoGuid,
                'statusCode' => $response->getStatusCode(),
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (Exception $e) {
            Log::error('BunnyCDN getVideo failed.', [
                'video_guid' => $videoGuid,
                'error' => $e->getMessage(),
            ]);

            throw new Exception("Video bilgileri alınamadı: " . $e->getMessage());
        }
    }

    /**
     * @link https://docs.bunny.net/reference/video_deletevideo
     */
    public function deleteVideo(string $videoId): bool
    {
        $client = new Client();

        $url = "https://video.bunnycdn.com/library/{$this->libraryId}/videos/{$videoId}";

        try {
            $response = $client->request('DELETE', $url, [
                'headers' => [
                    'AccessKey' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
            ]);

            Log::info('BunnyCDN video deleted successfully.', [
                'videoId' => $videoId,
                'statusCode' => $response->getStatusCode(),
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('BunnyCDN video delete failed.', [
                'videoId' => $videoId,
                'error' => $e->getMessage(),
            ]);

            throw new Exception("Video silinemedi: " . $e->getMessage());
        }
    }

    public function getThumbnailUrl(string $videoId, string $filename = 'thumbnail.jpg'): string
    {
        $cdnUrl = rtrim($this->cdnUrl, '/');
        $videoId = trim($videoId, '/');


        return "{$cdnUrl}/{$videoId}/{$filename}";
    }

    public function getStreamUrl(string $videoId): string
    {
        $cdnUrl = rtrim($this->cdnUrl, '/');
        $videoId = trim($videoId, '/');

        return "{$cdnUrl}/{$videoId}/playlist.m3u8";
    }

    /**
     * Get progressive MP4 URL for offline cache support
     * BunnyCDN provides MP4 files that can be downloaded and cached locally
     *
     * @param string $videoId Video GUID
     * @param string $resolution Resolution (360p, 720p, 1080p, or original)
     * @return string MP4 video URL
     */
    public function getMp4Url(string $videoId, string $resolution = '720p'): string
    {
        $cdnUrl = rtrim($this->cdnUrl, '/');
        $videoId = trim($videoId, '/');

        // BunnyCDN MP4 format: {cdn_url}/{video_guid}/play_{resolution}.mp4
        // For original quality, use: play.mp4
        $filename = $resolution === 'original' ? 'play.mp4' : "play_{$resolution}.mp4";

        return "{$cdnUrl}/{$videoId}/{$filename}";
    }

    public function getStorageUrl(string $path): string
    {
        $cdnUrl = rtrim($this->storageCdnUrl, '/');
        $cleanPath = ltrim($path, '/');

        return "{$cdnUrl}/{$cleanPath}";
    }

    /**
     * 🚀 CRITICAL FIX: Configure High-Quality Encoding Profiles
     *
     * Bu method video kalite sorununu çözüyor!
     * Bunny CDN'de yüksek kaliteli encoding ayarları yapılandırır
     *
     * @param string $videoGuid Video GUID
     * @return array Configuration result
     */
    public function configureHighQualityEncoding(string $videoGuid): array
    {
        try {
            $httpOptions = [];

            if (app()->environment('local')) {
                $httpOptions['verify'] = false;
            }

            // Bunny CDN API'ye yüksek kalite encoding profili gönder
            $response = Http::withOptions($httpOptions)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'AccessKey' => $this->apiKey
                ])
                ->post($this->apiUrl . "/videos/{$videoGuid}", [
                    // Encoding Quality Settings
                    'storageClass' => 'standard', // High-speed storage
                    'enabledResolutions' => '240p,360p,480p,720p,1080p', // All resolutions

                    // Video Quality Parameters - YÜKSEK KALİTE!
                    'videoQuality' => 95, // 0-100, 95 = YÜKSEK kalite
                    'encodingPreset' => 'slow', // slow = daha iyi kalite (fast, medium, slow)

                    // MP4 Fallback for cache
                    'generateMP4' => true,

                    // Thumbnail settings
                    'generateSprite' => true, // Timeline preview için
                ]);

            if ($response->successful()) {
                Log::info("✅ HIGH QUALITY encoding configured for video: {$videoGuid}", [
                    'video_guid' => $videoGuid,
                    'quality' => 95,
                    'preset' => 'slow'
                ]);

                return [
                    'success' => true,
                    'message' => 'High-quality encoding configured',
                    'data' => $response->json()
                ];
            }

            Log::warning("Failed to configure high-quality encoding", [
                'video_guid' => $videoGuid,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to configure encoding',
                'error' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error("High-quality encoding configuration error: {$e->getMessage()}", [
                'video_guid' => $videoGuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Exception during configuration',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 🎯 Configure Adaptive Bitrate Streaming (ABR)
     *
     * Internet hızına göre otomatik kalite ayarlaması
     * Mobil cihazlar için PERFECT!
     *
     * @param string $videoGuid Video GUID
     * @return array ABR configuration result
     */
    public function configureAdaptiveBitrate(string $videoGuid): array
    {
        try {
            $httpOptions = [];

            if (app()->environment('local')) {
                $httpOptions['verify'] = false;
            }

            // Multi-bitrate HLS manifest için encoding profiles
            $response = Http::withOptions($httpOptions)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'AccessKey' => $this->apiKey
                ])
                ->post($this->apiUrl . "/videos/{$videoGuid}", [
                    // ABR Encoding Profiles
                    'encodingProfiles' => [
                        [
                            'name' => '1080p',
                            'width' => 1920,
                            'height' => 1080,
                            'bitrate' => 5000, // 5 Mbps - Yüksek kalite
                            'fps' => 30,
                        ],
                        [
                            'name' => '720p',
                            'width' => 1280,
                            'height' => 720,
                            'bitrate' => 2800, // 2.8 Mbps - İyi internet için
                            'fps' => 30,
                        ],
                        [
                            'name' => '480p',
                            'width' => 854,
                            'height' => 480,
                            'bitrate' => 1400, // 1.4 Mbps - Orta internet
                            'fps' => 30,
                        ],
                        [
                            'name' => '360p',
                            'width' => 640,
                            'height' => 360,
                            'bitrate' => 800, // 800 Kbps - Yavaş internet
                            'fps' => 30,
                        ],
                        [
                            'name' => '240p',
                            'width' => 426,
                            'height' => 240,
                            'bitrate' => 400, // 400 Kbps - Çok yavaş internet
                            'fps' => 30,
                        ],
                    ],

                    // HLS Adaptive Streaming
                    'enableHlsAdaptiveStreaming' => true,
                    'hlsPlaylistType' => 'vod', // Video on demand
                ]);

            if ($response->successful()) {
                Log::info("✅ ABR (Adaptive Bitrate) configured for video: {$videoGuid}");

                return [
                    'success' => true,
                    'message' => 'ABR configured successfully',
                    'profiles' => ['1080p', '720p', '480p', '360p', '240p'],
                    'data' => $response->json()
                ];
            }

            Log::warning("ABR configuration failed", [
                'video_guid' => $videoGuid,
                'response' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => 'ABR configuration failed',
                'error' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error("ABR configuration error: {$e->getMessage()}", [
                'video_guid' => $videoGuid
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 📸 Get optimized thumbnail URL with WebP format
     *
     * WebP format: %30 daha küçük boyut, daha hızlı yükleme
     *
     * @param string $videoId Video GUID
     * @param string $filename Thumbnail filename
     * @param string $format Image format (webp, jpg, png)
     * @param int $width Width in pixels
     * @param int $quality Quality 1-100
     * @return string Optimized thumbnail URL
     */
    public function getOptimizedThumbnailUrl(
        string $videoId,
        string $filename = 'thumbnail.jpg',
        string $format = 'webp',
        int $width = 480,
        int $quality = 85
    ): string {
        $cdnUrl = rtrim($this->cdnUrl, '/');
        $videoId = trim($videoId, '/');

        $url = "{$cdnUrl}/{$videoId}/{$filename}";

        // Bunny CDN image optimization parameters
        $params = [];

        if ($format === 'webp') {
            $params[] = 'format=webp';
        }

        if ($width > 0) {
            $params[] = "width={$width}";
        }

        if ($quality > 0 && $quality <= 100) {
            $params[] = "quality={$quality}";
        }

        // Add optimization params
        if (!empty($params)) {
            $url .= '?' . implode('&', $params);
        }

        return $url;
    }

    /**
     * 🎬 Get video metadata with encoding status
     *
     * Video kalitesini ve encoding durumunu kontrol et
     *
     * @param string $videoGuid Video GUID
     * @return array Video metadata with encoding info
     */
    public function getVideoEncodingStatus(string $videoGuid): array
    {
        try {
            $httpOptions = [];

            if (app()->environment('local')) {
                $httpOptions['verify'] = false;
            }

            $response = Http::withOptions($httpOptions)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'AccessKey' => $this->apiKey
                ])
                ->get($this->apiUrl . "/videos/{$videoGuid}");

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'status' => $data['status'] ?? 'unknown',
                    'encoding_progress' => $data['encodeProgress'] ?? 0,
                    'available_resolutions' => $data['availableResolutions'] ?? [],
                    'video_quality' => $data['videoQuality'] ?? null,
                    'bitrate' => $data['averageBitrate'] ?? null,
                    'duration' => $data['length'] ?? 0,
                    'width' => $data['width'] ?? 0,
                    'height' => $data['height'] ?? 0,
                    'full_data' => $data
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to fetch video metadata'
            ];

        } catch (\Exception $e) {
            Log::error("Failed to get encoding status: {$e->getMessage()}");

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
