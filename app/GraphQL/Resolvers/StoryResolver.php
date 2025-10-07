<?php

namespace App\GraphQL\Resolvers;

use App\Models\Story;
use App\Models\StoryLike;
use App\Models\StoryView;
use App\Models\User;
use App\Services\BunnyCdnService;
use App\Jobs\ProcessStoryMediaUpload;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Nuwave\Lighthouse\Execution\ResolveInfo;
use Nuwave\Lighthouse\Execution\HttpGraphQLContext;

class StoryResolver
{
    protected $bunnyCdnService;

    public function __construct(BunnyCdnService $bunnyCdnService)
    {
        $this->bunnyCdnService = $bunnyCdnService;
    }

    /**
     * Create a new story
     *
     * @param null $_, array $args
     * @return Story
     * @throws \Exception
     */
    public function createStory($_, array $args)
    {
        $user = Auth::user();
        $input = $args['input'];

        $story = new Story();
        $story->user_id = $user->id;
        $story->caption = $input['caption'] ?? null;
        $story->background_color = $input['background_color'] ?? null;
        $story->text_color = $input['text_color'] ?? null;
        $story->font = $input['font'] ?? 'default';
        $story->status_id = Story::STATUS_SUCCESS;

        $story->expires_at = Carbon::now()->addHours(24);

        if (isset($input['media_type'])) {
            $story->media_type = $input['media_type'];
        } else {
            $story->media_type = 'image';
        }

        $story->save();

        if (isset($input['media_url']) && !empty($input['media_url'])) {
            $story->media_url = $input['media_url'];
            $story->status_id = Story::STATUS_SUCCESS;
            $story->save();
        }
        // Eğer media_data verilmişse, işleme koy
        else if (isset($input['media_data']) && !empty($input['media_data'])) {
            // Base64 veriyi işle ve BunnyCDN'e yükle
            try {
                // Media işleme işini kuyruğa ekle
                ProcessStoryMediaUpload::dispatch($story, $input['media_data']);
            } catch (\Exception $e) {
                Log::error('Story media upload error: ' . $e->getMessage());
                $story->status_id = Story::STATUS_FAILED;
                $story->save();
                throw new \Exception('Failed to process story media: ' . $e->getMessage());
            }
        }
        // Sadece metin içeren hikaye
        else if (isset($input['caption']) && !empty($input['caption'])) {
            $story->media_type = 'text';
            $story->status_id = Story::STATUS_SUCCESS;
            $story->save();
        }
        else {
            throw new \Exception('Either media_url, media_data or caption must be provided');
        }

        return $story;
    }

    /**
     * Update an existing story
     *
     * @param null $_, array $args
     * @return Story
     * @throws \Exception
     */
    public function updateStory($_, array $args)
    {
        $user = Auth::user();
        $storyId = $args['id'];
        $input = $args['input'];

        $story = Story::find($storyId);

        if (!$story) {
            throw new \Exception('Story not found');
        }

        if ($story->user_id != $user->id) {
            throw new \Exception('Unauthorized');
        }

        // Update story fields
        if (isset($input['caption'])) {
            $story->caption = $input['caption'];
        }

        if (isset($input['location'])) {
            $story->location = $input['location'];
        }

        if (isset($input['is_private'])) {
            $story->is_private = $input['is_private'];
        }

        if (isset($input['metadata'])) {
            $story->metadata = $input['metadata'];
        }

        $story->save();

        return $story;
    }

    /**
     * Delete a story
     *
     * @param null $_, array $args
     * @return bool
     * @throws \Exception
     */
    public function deleteStory($_, array $args)
    {
        $user = Auth::user();
        $storyId = $args['id'];

        $story = Story::find($storyId);

        if (!$story) {
            throw new \Exception('Story not found');
        }

        if ($story->user_id != $user->id) {
            throw new \Exception('Unauthorized');
        }

        // Delete the story from BunnyCDN
        try {
            if ($story->media_guid) {
                $this->bunnyCdnService->deleteVideo($story->media_guid);
            }
        } catch (\Exception $e) {
            Log::error('Failed to delete story media from BunnyCDN: ' . $e->getMessage());
            // Continue with deletion even if BunnyCDN deletion fails
        }

        // Delete the story from the database
        $story->delete();

        return true;
    }

    /**
     * Like a story
     *
     * @param null $_, array $args
     * @return bool
     * @throws \Exception
     */
    public function likeStory($_, array $args)
    {
        $user = Auth::user();
        $storyId = $args['story_id'];

        $story = Story::find($storyId);

        if (!$story) {
            throw new \Exception('Story not found');
        }

        // Check if story is expired
        if ($story->isExpired()) {
            throw new \Exception('Story is expired');
        }

        // Check if user already liked the story
        $existingLike = StoryLike::where('story_id', $storyId)
            ->where('user_id', $user->id)
            ->first();

        if ($existingLike) {
            return true; // Already liked
        }

        // Create like record
        $like = new StoryLike([
            'story_id' => $storyId,
            'user_id' => $user->id,
        ]);

        // Embed user data for performance
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'surname' => $user->surname,
            'nickname' => $user->nickname,
            'avatar' => $user->avatar,
            'is_private' => $user->is_private,
            'is_frozen' => $user->is_frozen,
            'collection_uuid' => $user->collection_uuid,
        ];
        $like->user_data = $userData;

        $like->save();

        // Increment likes count
        $story->increment('likes_count');

        return true;
    }

    /**
     * Unlike a story
     *
     * @param null $_, array $args
     * @return bool
     * @throws \Exception
     */
    public function unlikeStory($_, array $args)
    {
        $user = Auth::user();
        $storyId = $args['story_id'];

        $story = Story::find($storyId);

        if (!$story) {
            throw new \Exception('Story not found');
        }

        // Check if user liked the story
        $existingLike = StoryLike::where('story_id', $storyId)
            ->where('user_id', $user->id)
            ->first();

        if (!$existingLike) {
            return true; // Not liked, nothing to do
        }

        // Delete like record
        $existingLike->delete();

        // Decrement likes count
        if ($story->likes_count > 0) {
            $story->decrement('likes_count');
        }

        return true;
    }

    /**
     * Mark a story as viewed
     *
     * @param null $_, array $args
     * @return bool
     * @throws \Exception
     */
    public function viewStory($_, array $args)
    {
        $user = Auth::user();
        $storyId = $args['story_id'];
        $viewDuration = $args['view_duration'] ?? null;
        $completed = $args['completed'] ?? false;

        $story = Story::find($storyId);

        if (!$story) {
            throw new \Exception('Story not found');
        }

        // Check if story is expired
        if ($story->isExpired()) {
            throw new \Exception('Story is expired');
        }

        // Check if user already viewed the story
        $existingView = StoryView::where('story_id', $storyId)
            ->where('user_id', $user->id)
            ->first();

        if ($existingView) {
            // Update existing view
            $existingView->view_duration = $viewDuration;
            $existingView->completed = $completed;
            $existingView->save();
            return true;
        }

        // Create view record
        StoryView::create([
            'story_id' => $storyId,
            'user_id' => $user->id,
            'view_duration' => $viewDuration,
            'completed' => $completed,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_data' => [
                'id' => $user->id,
                'name' => $user->name,
                'surname' => $user->surname,
                'nickname' => $user->nickname,
                'avatar' => $user->avatar,
            ]
        ]);

        // Increment views count
        $story->increment('views_count');

        return true;
    }

    /**
     * Get a story by ID
     *
     * @param null $_, array $args
     * @return Story|null
     */
    public function story($_, array $args)
    {
        $storyId = $args['id'];
        return Story::find($storyId);
    }

    /**
     * Get stories from users the current user follows
     *
     * @param null $_, array $args
     * @return array
     */
    public function followingStories($_, array $args)
    {
        $user = Auth::user();
        $pagination = $args['pagination'] ?? ['page' => 1, 'per_page' => 10];
        $page = $pagination['page'];
        $perPage = $pagination['per_page'];

        $query = Story::active()
            ->fromFollowing($user->id)
            ->orderBy('created_at', 'desc');

        $total = $query->count();
        $stories = $query->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return [
            'stories' => $stories,
            'total_count' => $total,
            'has_more' => $total > ($page * $perPage),
        ];
    }

    /**
     * Get stories from a specific user
     *
     * @param null $_, array $args
     * @return array
     */
    public function userStories($_, array $args)
    {
        $currentUser = Auth::user();
        $userId = $args['user_id'];
        $pagination = $args['pagination'] ?? ['page' => 1, 'per_page' => 10];
        $filter = $args['filter'] ?? [];
        $page = $pagination['page'];
        $perPage = $pagination['per_page'];

        $user = User::find($userId);
        if (!$user) {
            throw new \Exception('User not found');
        }

        // Check if the user is private and the current user is not following them
        if ($user->is_private && $currentUser->id != $userId) {
            $isFollowing = \App\Models\Follow::where('follower_id', $currentUser->id)
                ->where('followed_id', $userId)
                ->where('status', 'accepted')
                ->exists();

            if (!$isFollowing) {
                return [
                    'stories' => [],
                    'total_count' => 0,
                    'has_more' => false,
                ];
            }
        }

        $query = Story::fromUser($userId);

        // Apply filters
        if (isset($filter['status'])) {
            $query->where('status_id', $filter['status']);
        } else {
            $query->where('status_id', Story::STATUS_SUCCESS);
        }

        if (isset($filter['is_private'])) {
            $query->where('is_private', $filter['is_private']);
        }

        if (isset($filter['from_date'])) {
            $query->where('created_at', '>=', $filter['from_date']);
        }

        if (isset($filter['to_date'])) {
            $query->where('created_at', '<=', $filter['to_date']);
        }

        $includeExpired = ($filter['include_expired'] ?? false) || ($currentUser->id == $userId);
        if (!$includeExpired) {
            $query->where('expires_at', '>', Carbon::now());
        }

        $query->orderBy('created_at', 'desc');

        $total = $query->count();
        $stories = $query->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return [
            'stories' => $stories,
            'total_count' => $total,
            'has_more' => $total > ($page * $perPage),
        ];
    }

    /**
     * Get the current user's stories
     *
     * @param null $_, array $args
     * @return array
     */
    public function myStories($_, array $args)
    {
        $user = Auth::user();
        $args['user_id'] = $user->id;
        return $this->userStories($_, $args);
    }

    /**
     * Get story likes
     *
     * @param null $_, array $args
     * @return array
     */
    public function storyLikes($_, array $args)
    {
        $storyId = $args['story_id'];
        $pagination = $args['pagination'] ?? ['page' => 1, 'per_page' => 10];
        $page = $pagination['page'];
        $perPage = $pagination['per_page'];

        $story = Story::find($storyId);
        if (!$story) {
            throw new \Exception('Story not found');
        }

        $query = StoryLike::where('story_id', $storyId)
            ->orderBy('created_at', 'desc');

        $total = $query->count();
        $likes = $query->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return [
            'likes' => $likes,
            'total_count' => $total,
            'has_more' => $total > ($page * $perPage),
        ];
    }

    /**
     * Get story views
     *
     * @param null $_, array $args
     * @return array
     */
    public function storyViews($_, array $args)
    {
        $storyId = $args['story_id'];
        $pagination = $args['pagination'] ?? ['page' => 1, 'per_page' => 10];
        $page = $pagination['page'];
        $perPage = $pagination['per_page'];

        $story = Story::find($storyId);
        if (!$story) {
            throw new \Exception('Story not found');
        }

        $query = StoryView::where('story_id', $storyId)
            ->orderBy('created_at', 'desc');

        $total = $query->count();
        $views = $query->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return [
            'views' => $views,
            'total_count' => $total,
            'has_more' => $total > ($page * $perPage),
        ];
    }

    /**
     * Upload media and create a story
     *
     * @param null $_, array $args
     * @return array
     * @throws \Exception
     */
    public function uploadStoryMedia($_, array $args, HttpGraphQLContext $context, ResolveInfo $info)
    {
        try {
            Log::info('Story upload started', ['user_agent' => request()->userAgent()]);
            
            $user = Auth::user();
            if (!$user) {
                throw new \Exception('Kullanıcı bulunamadı');
            }

        $input = $args['input'] ?? [];

        // Try to get the file from the request
        $file = $context->request->file('media');
        $mediaData = null;
        $mimeType = null;
        $extension = null;

        if ($file) {
            // Handle file upload
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4'];
            $mimeType = $file->getMimeType();

            if (!in_array($mimeType, $allowedTypes)) {
                throw new \Exception('Geçersiz dosya türü. JPEG, PNG, GIF veya MP4 yükleyebilirsiniz.');
            }

            // Get file extension
            $extension = $file->getClientOriginalExtension();

            // Read the file data
            $mediaData = file_get_contents($file->getRealPath());
        } else if (isset($input['media_base64']) && !empty($input['media_base64'])) {
            // Handle base64 encoded data
            $mediaBase64 = $input['media_base64'];
            $mediaType = $input['media_type'] ?? null;

            if (!$mediaBase64 || !$mediaType) {
                throw new \Exception('Medya verisi veya türü bulunamadı');
            }

            // Log the received media type for debugging
            \Log::info('Received media type for base64 upload: ' . $mediaType);

            // Validate media type - handle both full MIME types and simple types
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4'];
            $allowedSimpleTypes = ['image', 'video'];

            // Check if it's a full MIME type
            $isValidMimeType = in_array($mediaType, $allowedMimeTypes);

            // Check if it's a simple type (starts with 'image' or 'video')
            $isValidSimpleType = false;
            foreach ($allowedSimpleTypes as $simpleType) {
                if (strpos($mediaType, $simpleType) === 0) {
                    $isValidSimpleType = true;
                    break;
                }
            }

            if (!$isValidMimeType && !$isValidSimpleType) {
                throw new \Exception('Geçersiz dosya türü. JPEG, PNG, GIF veya MP4 yükleyebilirsiniz.');
            }

            // Determine the correct MIME type
            if ($isValidSimpleType && !$isValidMimeType) {
                // If it's a simple type like 'image', default to a common format
                if (strpos($mediaType, 'image') === 0) {
                    $mimeType = 'image/jpeg';
                } else if (strpos($mediaType, 'video') === 0) {
                    $mimeType = 'video/mp4';
                }
            } else {
                $mimeType = $mediaType;
            }

            // Decode base64 data
            // Check if the string contains a data URI scheme
            if (strpos($mediaBase64, ';base64,') !== false) {
                // Extract the base64 encoded data from the data URI
                list(, $mediaBase64) = explode(';base64,', $mediaBase64);
            }
            
            // Log the length of the base64 string for debugging
            Log::info('Base64 data received', [
                'base64_length' => strlen($mediaBase64),
                'media_type' => $mediaType
            ]);
            
            // Validate base64 string
            if (empty($mediaBase64)) {
                throw new \Exception('Boş medya verisi alındı');
            }
            
            // Check if base64 string is valid
            if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $mediaBase64)) {
                Log::warning('Received potentially invalid base64 data', [
                    'base64_sample' => substr($mediaBase64, 0, 50) . '...'
                ]);
            }
            
            $mediaData = base64_decode($mediaBase64, true);
            
            if ($mediaData === false) {
                throw new \Exception('Medya verisi geçerli base64 formatında değil');
            }
            
            if (empty($mediaData)) {
                throw new \Exception('Medya verisi çözümlenemedi veya boş');
            }
            
            // Log the decoded data size
            Log::info('Base64 data decoded successfully', [
                'decoded_size' => strlen($mediaData)
            ]);

            // Determine extension from mime type
            $extensions = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'video/mp4' => 'mp4'
            ];
            $extension = $extensions[$mimeType] ?? 'jpg';
        } else {
            throw new \Exception('Medya dosyası bulunamadı');
        }

        // Determine media type from mime type
        $mediaType = strpos($mimeType, 'image/') === 0 ? 'image' : 'video';
        if (empty($extension)) {
            $extension = $mediaType === 'image' ? 'jpg' : 'mp4';
        }

        // Generate unique ID for the media
        $mediaGuid = (string) Str::uuid();

        // Calculate expiration time (default: 24 hours)
        $duration = $input['duration'] ?? 24;
        $expiresAt = Carbon::now()->addHours($duration);

        // Create story record
        $story = Story::create([
            'user_id' => $user->id,
            'media_guid' => $mediaGuid,
            'media_type' => $mediaType,
            'media_url' => null, // Will be updated after upload
            'thumbnail_url' => null, // Will be updated after upload
            'caption' => $input['caption'] ?? null,
            'location' => $input['location'] ?? null,
            'is_private' => $input['is_private'] ?? false,
            'expires_at' => $expiresAt,
            'views_count' => 0,
            'likes_count' => 0,
            'status_id' => Story::STATUS_SUCCESS, // Doğrudan active olarak ayarlıyoruz
            'metadata' => $input['metadata'] ?? null,
        ]);

        // Embed user data for performance
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'surname' => $user->surname,
            'nickname' => $user->nickname,
            'avatar' => $user->avatar,
        ];
        $story->user_data = $userData;

        $story->save();

        // Save media data to a temporary file
        $tempPath = storage_path('app/temp') . '/' . $mediaGuid . '_story.' . $extension;

        // Ensure the temp directory exists
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }
        
        // Make sure the directory is writable
        $tempDir = storage_path('app/temp');
        if (!is_writable($tempDir)) {
            Log::error('Temp directory is not writable', [
                'temp_dir' => $tempDir,
                'permissions' => substr(sprintf('%o', fileperms($tempDir)), -4)
            ]);
            
            // Try to fix permissions
            chmod($tempDir, 0755);
            
            if (!is_writable($tempDir)) {
                throw new \Exception('Geçici dizin yazılabilir değil');
            }
        }

        // Write the media data to the temp file
        $bytesWritten = file_put_contents($tempPath, $mediaData);

        if ($bytesWritten === false) {
            \Log::error('Failed to write temporary file for story media', [
                'story_id' => $story->id,
                'media_guid' => $mediaGuid,
                'temp_path' => $tempPath,
                'media_data_size' => strlen($mediaData)
            ]);

            // Update story status to failed
            $story->status_id = Story::STATUS_FAILED;
            $story->save();

            throw new \Exception('Medya dosyası kaydedilemedi');
        }
        
        // Verify the file was written correctly
        if ($bytesWritten !== strlen($mediaData)) {
            \Log::error('Incomplete write of temporary file for story media', [
                'story_id' => $story->id,
                'media_guid' => $mediaGuid,
                'temp_path' => $tempPath,
                'expected_bytes' => strlen($mediaData),
                'written_bytes' => $bytesWritten
            ]);
            
            // Update story status to failed
            $story->status_id = Story::STATUS_FAILED;
            $story->save();
            
            throw new \Exception('Medya dosyası tam olarak kaydedilemedi');
        }
        
        // Verify the file exists and has the correct size
        if (!file_exists($tempPath) || filesize($tempPath) !== $bytesWritten) {
            \Log::error('Temporary file verification failed for story media', [
                'story_id' => $story->id,
                'media_guid' => $mediaGuid,
                'temp_path' => $tempPath,
                'expected_size' => $bytesWritten,
                'actual_size' => file_exists($tempPath) ? filesize($tempPath) : 'file does not exist'
            ]);
            
            // Update story status to failed
            $story->status_id = Story::STATUS_FAILED;
            $story->save();
            
            throw new \Exception('Medya dosyası doğrulanamadı');
        }

        \Log::info('Temporary file created for story media', [
            'story_id' => $story->id,
            'media_guid' => $mediaGuid,
            'temp_path' => $tempPath,
            'file_size' => $bytesWritten,
            'file_exists' => file_exists($tempPath)
        ]);

        // Doğrudan varsayılan Laravel queue kullan, RabbitMQ kullanma
        try {
            // Hikayenin işlenmesi için job'ı kuyruğa ekle
            ProcessStoryMediaUpload::dispatch($story->id, $mediaGuid, $extension, $tempPath, $user->id);
                
            // Log the job dispatch for debugging
            Log::info('ProcessStoryMediaUpload job dispatched via default queue', [
                'story_id' => $story->id,
                'media_guid' => $mediaGuid,
                'connection' => 'default'
            ]);
            
            // Hikaye durumunu güncelle - işleniyor olarak işaretle
            $story->status_id = Story::STATUS_PROCESSING;
            $story->save();
        } catch (\Exception $e) {
            // Log the queue dispatch failure
            Log::error('Queue dispatch failed', [
                'story_id' => $story->id,
                'media_guid' => $mediaGuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Hikaye durumunu hata olarak işaretle
            $story->status_id = Story::STATUS_FAILED;
            $story->save();
            
            // Kullanıcıya hata mesajı göster
            throw new \Exception('Hikaye işleme kuyruğuna eklenirken bir hata oluştu: ' . $e->getMessage());
        }

        // Return the story immediately, even though the upload is still in progress
        Log::info('Story upload completed successfully', ['story_id' => $story->id]);
        return $story;
        } catch (\Exception $e) {
            Log::error('Story upload failed with exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_agent' => request()->userAgent()
            ]);
            
            // Clean up any temporary file if it exists
            if (isset($tempPath) && file_exists($tempPath)) {
                @unlink($tempPath);
            }
            
            // If a story was created but processing failed, mark it as failed or delete it
            if (isset($story) && $story->id) {
                $story->status_id = Story::STATUS_FAILED;
                $story->save();
            }
            
            throw $e;
        }
    }

    public function resolveUser($rootValue, array $args)
    {
        return User::find($rootValue->user_id);
    }

    public function resolveStory($rootValue, array $args)
    {
        return Story::find($rootValue->story_id);
    }
}
