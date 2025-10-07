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
        $this->storageApiKey = config('bunnycdn-storage.api_key', env('BUNNYCDN_STORAGE_API_KEY', ''));
        $this->storageZoneName = config('bunnycdn-storage.zone_name', env('BUNNYCDN_STORAGE_ZONE_NAME', ''));

        // Get region and base hostname from config
        $region = config('bunnycdn-storage.region', '');
        $baseHostname = config('bunnycdn-storage.base_hostname', 'storage.bunnycdn.com');

        // Build the storage URL with or without region prefix
        $hostname = !empty($region) ? "{$region}.{$baseHostname}" : $baseHostname;

        // This URL is used for API operations (uploading files)
        $this->storageUrl = "https://{$hostname}";

        // Set the CDN URL for serving media files
        // Use the configured CDN URL or fall back to the default pull zone URL
        $this->storageCdnUrl = config('bunnycdn-storage.cdn_url', env('BUNNYCDN_STORAGE_CDN_URL', ''));

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
            }

            // Create paths for media and thumbnail
            // If userId is provided, organize files by user: {contentFolder}/{userId}/{guid}/
            // Otherwise use the original structure: {contentFolder}/{guid}/
            $basePath = $contentFolder . '/';

            // Always include userId in the path if available
            if (!empty($userId)) {
                $basePath .= $userId . '/';
            }

            $basePath .= $guid . '/';

            // Determine file name based on content type
            $fileName = ($contentType === 'stream') ? 'thumbnail' : 'story';
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
     * Upload a file to BunnyCDN Storage
     *
     * @param string $path Path in storage zone (without leading slash)
     * @param string $content File content
     * @return bool Success status
     */
    public function uploadToStorage(string $path, string $content): bool
    {
        try {
            // Validate storage configuration with detailed logging
            if (empty($this->storageApiKey) || empty($this->storageZoneName) || empty($this->storageUrl)) {
                Log::error('BunnyCDN Storage configuration is incomplete', [
                    'storage_api_key_set' => !empty($this->storageApiKey),
                    'storage_zone_name_set' => !empty($this->storageZoneName),
                    'storage_url_set' => !empty($this->storageUrl),
                    'storage_api_key_length' => empty($this->storageApiKey) ? 0 : strlen($this->storageApiKey),
                    'storage_zone_name' => $this->storageZoneName,
                    'storage_url' => $this->storageUrl,
                    'env_storage_api_key_set' => !empty(env('BUNNYCDN_STORAGE_API_KEY')),
                    'env_storage_zone_name_set' => !empty(env('BUNNYCDN_STORAGE_ZONE_NAME')),
                    'env_storage_cdn_url_set' => !empty(env('BUNNYCDN_STORAGE_CDN_URL'))
                ]);
                return false;
            }

            // Log content size for debugging
            $contentSize = strlen($content);
            Log::info('Preparing to upload content to BunnyCDN Storage', [
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

            // HTTP isteği için gerekli başlıkları ayarla
            $headers = [
                'Content-Type: application/octet-stream',
                'AccessKey: ' . $this->storageApiKey
            ];

            // HTTP bağlam seçeneklerini ayarla
            $options = [
                'http' => [
                    'method' => 'PUT',
                    'header' => implode('\r\n', $headers),
                    'content' => $fileContent,
                    'timeout' => 60,
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

                // Curl ile tekrar deneyelim
                return $this->uploadToStorageWithCurl($url, $content, $path);
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
     * @return bool Success status
     */
    private function uploadToStorageWithCurl(string $url, string $content, string $path): bool
    {
        try {
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
                    "AccessKey: {$this->storageApiKey}",
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
                return false;
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

    public function getStorageUrl(string $path): string
    {
        $cdnUrl = rtrim($this->storageCdnUrl, '/');
        $cleanPath = ltrim($path, '/');

        return "{$cdnUrl}/{$cleanPath}";
    }
}
