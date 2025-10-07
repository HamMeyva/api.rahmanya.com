<?php

namespace App\Jobs;

use App\Models\Story;
use App\Services\BunnyCdnService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ProcessStoryMediaUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;
    // Removed SerializesModels trait which was causing issues with serialization

    protected $storyId;
    protected $mediaGuid;
    protected $extension;
    public $mediaPath;
    protected $userId;

    /**
     * Create a new job instance.
     * 
     * @param string $storyId The ID of the story
     * @param string $mediaGuid The media GUID
     * @param string $extension The file extension
     * @param string $mediaPath The path to the temporary media file
     * @param string|null $userId The ID of the user who owns the story
     */
    public function __construct(string $storyId, string $mediaGuid, string $extension, string $mediaPath, ?string $userId = null)
    {
        $this->storyId = $storyId;
        $this->mediaGuid = $mediaGuid;
        $this->extension = $extension;
        $this->mediaPath = $mediaPath;
        $this->userId = $userId;
        
        // Log the file path when the job is created
        Log::info('ProcessStoryMediaUpload job created', [
            'story_id' => $this->storyId,
            'user_id' => $this->userId,
            'media_guid' => $this->mediaGuid,
            'media_path' => $this->mediaPath
        ]);
    }

    /**
     * Prepare the instance for serialization.
     *
     * @return array
     */
    public function __sleep()
    {
        // Log before serialization
        Log::info('ProcessStoryMediaUpload job serializing', [
            'story_id' => $this->storyId,
            'user_id' => $this->userId,
            'media_guid' => $this->mediaGuid,
            'media_path' => $this->mediaPath
        ]);
        
        // Return all the properties that should be serialized
        return ['storyId', 'mediaGuid', 'extension', 'mediaPath', 'userId'];
    }
    
    /**
     * Prepare the instance after serialization.
     *
     * @return void
     */
    public function __wakeup()
    {
        // Log after deserialization
        Log::info('ProcessStoryMediaUpload job waking up', [
            'story_id' => $this->storyId,
            'user_id' => $this->userId,
            'media_guid' => $this->mediaGuid,
            'media_path' => $this->mediaPath
        ]);
    }

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 10;

    /**
     * Execute the job.
     */
    public function handle(BunnyCdnService $bunnyCdnService): void
    {
        try {
            Log::info('Processing story media upload job', [
                'story_id' => $this->storyId,
                'user_id' => $this->userId,
                'media_guid' => $this->mediaGuid,
                'media_path' => $this->mediaPath
            ]);

            // Find the story
            $story = Story::find($this->storyId);
            if (!$story) {
                Log::error('Story not found for media upload', [
                    'story_id' => $this->storyId,
                    'media_guid' => $this->mediaGuid
                ]);
                
                // Hikaye bulunamadıysa, MongoDB'de doğrudan ID kullanarak arayalım
                // MongoDB'de ObjectId formatında olabilir
                $story = Story::where('_id', $this->storyId)->first();
                
                if (!$story) {
                    Log::error('Story still not found after MongoDB search', [
                        'story_id' => $this->storyId,
                        'media_guid' => $this->mediaGuid
                    ]);
                    return;
                }
                
                Log::info('Story found using MongoDB query', [
                    'story_id' => $story->id,
                    'media_guid' => $this->mediaGuid
                ]);
            }
            
            // Get the user ID from the story if not provided in the constructor
            $userId = $this->userId;
            if (empty($userId)) {
                $userId = $story->user_id;
                Log::info('Using user ID from story record', [
                    'story_id' => $this->storyId,
                    'user_id' => $userId
                ]);
            }
            
            // Ensure we have a user ID
            if (empty($userId)) {
                Log::warning('User ID not found for story media upload', [
                    'story_id' => $this->storyId,
                    'media_guid' => $this->mediaGuid
                ]);
                // We'll continue with upload but without user ID organization
            }

            // If mediaPath is null, try to reconstruct it
            if (!$this->mediaPath) {
                Log::warning('Media path is null, attempting to reconstruct', [
                    'story_id' => $this->storyId,
                    'media_guid' => $this->mediaGuid
                ]);
                
                // Reconstruct the path using the same pattern as in StoryResolver
                $this->mediaPath = storage_path('app/temp') . '/' . $this->mediaGuid . '_story.' . $this->extension;
                
                Log::info('Reconstructed media path', [
                    'story_id' => $this->storyId,
                    'media_guid' => $this->mediaGuid,
                    'reconstructed_path' => $this->mediaPath
                ]);
            }

            // Read the media data from the temporary file
            if (!file_exists($this->mediaPath)) {
                Log::error('Temp file does not exist for story media upload', [
                    'story_id' => $this->storyId,
                    'media_guid' => $this->mediaGuid,
                    'temp_file_path' => $this->mediaPath
                ]);
                $story->status = 'failed';
                $story->save();
                return;
            }
            
            // Check file size
            $fileSize = filesize($this->mediaPath);
            if ($fileSize === 0) {
                Log::error('Temp file is empty for story media upload', [
                    'story_id' => $this->storyId,
                    'media_guid' => $this->mediaGuid,
                    'temp_file_path' => $this->mediaPath
                ]);
                $story->status = 'failed';
                $story->save();
                return;
            }

            // Check if the file is readable
            if (!is_readable($this->mediaPath)) {
                Log::error('Temp file is not readable for story media upload', [
                    'story_id' => $this->storyId,
                    'media_guid' => $this->mediaGuid,
                    'temp_file_path' => $this->mediaPath,
                    'file_permissions' => substr(sprintf('%o', fileperms($this->mediaPath)), -4)
                ]);
                $story->status = 'failed';
                $story->save();
                return;
            }

            // Log file details before reading
            $fileSize = filesize($this->mediaPath);
            Log::info('Reading temporary file for story media upload', [
                'story_id' => $this->storyId,
                'user_id' => $this->userId,
                'media_guid' => $this->mediaGuid,
                'temp_file_path' => $this->mediaPath,
                'file_size' => $fileSize
            ]);

            $mediaData = file_get_contents($this->mediaPath);
            
            if ($mediaData === false) {
                Log::error('Failed to read temp file for story media upload', [
                    'story_id' => $this->storyId,
                    'media_guid' => $this->mediaGuid,
                    'temp_file_path' => $this->mediaPath
                ]);
                $story->status = 'failed';
                $story->save();
                return;
            }
            
            // Compress image if it's an image file (not a video)
            if (in_array($this->extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                try {
                    Log::info('Compressing image for story media upload', [
                        'story_id' => $this->storyId,
                        'user_id' => $this->userId,
                        'media_guid' => $this->mediaGuid,
                        'original_size' => strlen($mediaData)
                    ]);
                    
                    // Create a new ImageManager instance with the GD driver
                    $manager = new ImageManager(new Driver());
                    
                    // Create an image instance from the media data
                    $image = $manager->read($mediaData);
                    
                    // Get original dimensions
                    $width = $image->width();
                    $height = $image->height();
                    $fileSize = strlen($mediaData);
                    
                    // Resmin orijinal en-boy oranını tamamen koru, hiçbir şekilde 1920 piksel yüksekliğe tamamlama
                    // Sadece çok büyük görselleri küçült, küçük görselleri olduğu gibi bırak
                    if ($width > 1080 || $height > 1920) {
                        // En-boy oranını koru, ancak maksimum boyutları aşma
                        $aspectRatio = $width / $height;
                        
                        if ($aspectRatio > (1080/1920)) { // Daha geniş resim
                            // Genişliği 1080'e sınırla, yüksekliği orantılı olarak ayarla
                            $newWidth = 1080;
                            $newHeight = intval(1080 / $aspectRatio);
                        } else { // Daha uzun resim
                            // Yüksekliği 1920'ye sınırla, genişliği orantılı olarak ayarla
                            $newHeight = 1920;
                            $newWidth = intval(1920 * $aspectRatio);
                        }
                        
                        // ÖNEMLİ: Resmi yeniden boyutlandır - kesinlikle arka plan dolgusu OLMADAN
                        // resize() metodu kullanıyoruz ve sadece boyutları belirtiyoruz
                        // constrainAspectRatio parametresini true olarak ayarlıyoruz (varsayılan değer)
                        // upsize parametresini false olarak ayarlıyoruz ki görsel büyütülmesin
                        // bu sayede görsel kesinlikle orijinal en-boy oranını korur ve arkaplan eklenmez
                        $image->resize($newWidth, $newHeight, function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        });
                        
                        Log::info('Image resized while preserving aspect ratio without background fill', [
                            'story_id' => $this->storyId,
                            'user_id' => $this->userId,
                            'original_width' => $width,
                            'original_height' => $height,
                            'aspect_ratio' => $aspectRatio,
                            'new_width' => $newWidth,
                            'new_height' => $newHeight
                        ]);
                    } else {
                        // Resim zaten uygun boyutlarda, boyutlandırma yapma
                        Log::info('Image is already within acceptable dimensions, no resizing needed', [
                            'story_id' => $this->storyId,
                            'user_id' => $this->userId,
                            'width' => $width,
                            'height' => $height
                        ]);
                    }
                    
                    // Dosya boyutu 10MB'dan büyükse, görüntü kalitesini koruyarak sıkıştır
                    $quality = 90; // Varsayılan kalite
                    if ($fileSize > 10 * 1024 * 1024) { // 10MB
                        $quality = 85;
                    } else if ($fileSize > 5 * 1024 * 1024) { // 5MB
                        $quality = 88;
                    }
                    
                    // Görüntüyü belirlenen kalitede sıkıştır
                    $compressedData = $image->toJpeg($quality)->toString();
                    
                    // Use the compressed data if it's smaller than the original
                    if (strlen($compressedData) < strlen($mediaData)) {
                        $mediaData = $compressedData;
                        Log::info('Image compressed successfully', [
                            'story_id' => $this->storyId,
                            'user_id' => $this->userId,
                            'media_guid' => $this->mediaGuid,
                            'original_size' => strlen($mediaData),
                            'compressed_size' => strlen($compressedData),
                            'reduction_percentage' => round((1 - (strlen($compressedData) / strlen($mediaData))) * 100, 2) . '%'
                        ]);
                    } else {
                        Log::info('Compression not applied - original is smaller', [
                            'story_id' => $this->storyId,
                            'user_id' => $this->userId,
                            'media_guid' => $this->mediaGuid
                        ]);
                    }
                } catch (\Exception $e) {
                    // Log the error but continue with the original data if compression fails
                    Log::warning('Image compression failed, using original image', [
                        'story_id' => $this->storyId,
                        'user_id' => $this->userId,
                        'media_guid' => $this->mediaGuid,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Log the media data size before upload
            Log::info('Media data size before upload', [
                'story_id' => $this->storyId,
                'media_guid' => $this->mediaGuid,
                'media_data_size' => strlen($mediaData),
                'extension' => $this->extension
            ]);
            
            // Check BunnyCDN configuration before attempting upload
            Log::info('Checking BunnyCDN configuration before upload', [
                'story_id' => $this->storyId,
                'media_guid' => $this->mediaGuid,
                'storage_api_key_set' => !empty(env('BUNNYCDN_STORAGE_API_KEY')),
                'storage_zone_name_set' => !empty(env('BUNNYCDN_STORAGE_ZONE_NAME')),
                'storage_cdn_url_set' => !empty(env('BUNNYCDN_STORAGE_CDN_URL'))
            ]);
            
            // Upload to BunnyCDN with user ID
            try {
                // Attempt the upload - ensure we're using the correct userId parameter
                // Get the user ID from the story if not provided in the constructor
                $userId = $this->userId;
                if (empty($userId)) {
                    $userId = $story->user_id;
                    Log::info('Using user ID from story record for BunnyCDN upload', [
                        'story_id' => $this->storyId,
                        'user_id' => $userId
                    ]);
                }
                
                $uploadResult = $bunnyCdnService->uploadMedia($mediaData, $this->mediaGuid, $this->extension, $userId);
                
                // Verify the upload result contains the expected data
                if (!isset($uploadResult['guid']) || !isset($uploadResult['url']) || !isset($uploadResult['thumbnail_url'])) {
                    Log::error('BunnyCDN upload returned incomplete result', [
                        'story_id' => $this->storyId,
                        'media_guid' => $this->mediaGuid,
                        'result' => $uploadResult
                    ]);
                    throw new \Exception('BunnyCDN upload returned incomplete result');
                }
                
                // Validate the URLs returned
                if (empty($uploadResult['url']) || empty($uploadResult['thumbnail_url'])) {
                    Log::error('BunnyCDN upload returned empty URLs', [
                        'story_id' => $this->storyId,
                        'media_guid' => $this->mediaGuid,
                        'url' => $uploadResult['url'] ?? 'missing',
                        'thumbnail_url' => $uploadResult['thumbnail_url'] ?? 'missing'
                    ]);
                    throw new \Exception('BunnyCDN upload returned empty URLs');
                }
            } catch (\Exception $e) {
                Log::error('Exception during BunnyCDN upload: ' . $e->getMessage(), [
                    'story_id' => $this->storyId,
                    'media_guid' => $this->mediaGuid,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'storage_api_key_set' => !empty(env('BUNNYCDN_STORAGE_API_KEY')),
                    'storage_zone_name_set' => !empty(env('BUNNYCDN_STORAGE_ZONE_NAME')),
                    'storage_cdn_url_set' => !empty(env('BUNNYCDN_STORAGE_CDN_URL'))
                ]);
                $story->status = 'failed';
                $story->save();
                // Clean up temporary file
                @unlink($this->mediaPath);
                return;
            }

            // Delete the temporary file
            @unlink($this->mediaPath);

            if (!isset($uploadResult['guid'])) {
                Log::error('Failed to upload story media to CDN', [
                    'story_id' => $this->storyId,
                    'user_id' => $this->userId,
                    'media_guid' => $this->mediaGuid
                ]);
                
                // Update story status to failed
                $story->status = 'failed';
                $story->save();
                return;
            }

            // Set media URLs from upload result
            $mediaUrl = $uploadResult['url'] ?? null;
            $thumbnailUrl = $uploadResult['thumbnail_url'] ?? null;

            // Update the story with the media URLs
            $story->media_url = $mediaUrl;
            $story->thumbnail_url = $thumbnailUrl;
            $story->status = 'active'; // Update status to active
            $story->save();

            Log::info('Story media uploaded successfully', [
                'story_id' => $this->storyId,
                'user_id' => $this->userId,
                'media_guid' => $this->mediaGuid
            ]);
        } catch (\Exception $e) {
            Log::error('BunnyCDN upload error: ' . $e->getMessage(), [
                'story_id' => $this->storyId,
                'user_id' => $this->userId,
                'media_guid' => $this->mediaGuid,
                'error' => $e->getMessage()
            ]);
            
            // Update story status to failed if story exists
            $story = Story::find($this->storyId);
            if ($story) {
                $story->status = 'failed';
                $story->save();
            }
            
            // Clean up temporary file
            @unlink($this->mediaPath);
        }
    }
}
