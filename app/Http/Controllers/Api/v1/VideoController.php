<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\VideoResource;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoComment;
use App\Models\VideoLike;
use App\Models\VideoView;
use App\Services\BunnyCdnService;
use App\Services\VideoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class VideoController extends Controller
{
    protected $bunnyService;
    protected $videoService;

    public function __construct(BunnyCdnService $bunnyService, VideoService $videoService)
    {
        $this->bunnyService = $bunnyService;
        $this->videoService = $videoService;
    }

    /**
     * Get a list of videos
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $filter = $request->input('filter', 'latest');
        $tag = $request->input('tag');
        $teamTag = $request->input('team_tag');
        $athleteTag = $request->input('athlete_tag');

        $query = Video::query()->where('is_private', false);

        // Apply filters
        if ($tag) {
            $query->where('tags', 'all', [$tag]);
        }

        if ($teamTag) {
            $query->where('team_tags', 'all', [$teamTag]);
        }

        // Apply sorting
        switch ($filter) {
            case 'trending':
                $query->orderBy('trending_score', 'desc');
                break;
            case 'popular':
                $query->orderBy('views_count', 'desc');
                break;
            case 'engagement':
                $query->orderBy('engagement_score', 'desc');
                break;
            case 'latest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $videos = $query->with(['user'])
            ->withCount('video_likes')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => VideoResource::collection($videos),
            'meta' => [
                'current_page' => $videos->currentPage(),
                'last_page' => $videos->lastPage(),
                'per_page' => $videos->perPage(),
                'total' => $videos->total(),
            ],
        ]);
    }

    /**
     * Get a personalized feed of videos
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function feed(Request $request)
    {
        $options = [
            'page' => $request->input('page', 1),
            'per_page' => $request->input('per_page', 10),
            'bypass_cache' => $request->input('bypass_cache', false),
        ];

        $user = $request->user();
        $feedData = $this->videoService->generatePersonalizedFeed($user, $options);

        return response()->json([
            'success' => true,
            'data' => VideoResource::collection($feedData['videos']),
            'meta' => [
                'current_page' => $feedData['page'],
                'per_page' => $feedData['per_page'],
                'total' => $feedData['total'],
            ],
        ]);
    }

    /**
     * Get trending videos
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function trending(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        $videos = Video::where('is_private', false)
            ->orderBy('trending_score', 'desc')
            ->with(['user'])
            ->withCount('video_likes')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => VideoResource::collection($videos),
            'meta' => [
                'current_page' => $videos->currentPage(),
                'last_page' => $videos->lastPage(),
                'per_page' => $videos->perPage(),
                'total' => $videos->total(),
            ],
        ]);
    }

    /**
     * Record a video view
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function recordView(Request $request, $id)
    {
        $video = Video::find($id);

        if (!$video) {
            return response()->json([
                'success' => false,
                'message' => 'Video not found',
            ], 404);
        }

        $user = $request->user();
        $userId = $user ? $user->id : null;

        // For anonymous views, we'll use a placeholder or IP hash
        if (!$userId) {
            // Use a hashed IP as identifier for anonymous users
            $ipHash = md5($request->ip());
            $userId = 'anon_' . $ipHash;
        }

        // Create a video view record
        $viewData = [
            'video_id' => $video->id,
            'user_id' => $userId,
            'viewed_at' => now(),
            'completed' => $request->input('completed', false),
            'view_duration' => $request->input('duration', 0),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        // Store the view
        $videoView = VideoView::create($viewData);

        // Track the interaction using our optimized method
        $this->videoService->trackUserInteraction(
            $video->id,
            $userId,
            'view',
            true // Update scores
        );

        return response()->json([
            'success' => true,
            'message' => 'View recorded successfully',
            'data' => [
                'view_id' => $videoView->id,
            ],
        ]);
    }

    /**
     * Get a single video
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        $video = Video::with(['user', 'video_comments.user', 'video_comments.replies.user'])
            ->withCount('video_likes')
            ->find($id);

        if (!$video) {
            return response()->json([
                'success' => false,
                'message' => 'Video not found',
            ], 404);
        }

        // Check if the video is private and if the user is authorized to view it
        if ($video->is_private && (!$request->user() || $request->user()->id !== $video->user_id)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to view this video',
            ], 403);
        }

        // Increment view count
        $video->increment('views_count');

        return response()->json([
            'success' => true,
            'data' => new VideoResource($video),
        ]);
    }

    /**
     * Create a BunnyCDN upload URL for client-side uploads
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createUploadUrl(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $title = $request->input('title');

        try {
            $uploadData = $this->bunnyService->createVideoUploadUrl(
                $title,
                $user->collection_uuid ?? null
            );

            if (empty($uploadData['uploadUrl']) || empty($uploadData['videoId'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create upload URL',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'uploadUrl' => $uploadData['uploadUrl'],
                    'videoId' => $uploadData['videoId'],
                    'expiresAt' => $uploadData['expiresAt'] ?? null,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create upload URL: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create upload URL: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process video metadata authenticated with Server Key
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function processMetadataWithKey(Request $request)
    {
        $serverKey = $request->header('X-Server-Key') ?? $request->input('server_key');

        if ($serverKey !== 'dba2e326d47e1f7ed887724b39aa281e') {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Server Key',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'video_id' => 'required|string',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'product_id' => 'required|string', // Ensure product_id is provided
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Ideally we should map the Seller to a User.
        // For now, let's find a default "Seller" user or create one, OR pick the first admin.
        // Or if we can send a trusted user_id from the Seller App (if known).
        // Let's use a placeholder user or the first user for now to avoid constraint errors.
        // IMPROVEMENT: Create a specific 'System Seller' user in migration.
        $user = User::first();

        $videoId = $request->input('video_id');
        $metadata = $request->all();

        // Ensure we pass the user object expected by videoService
        $result = $this->videoService->processVideoMetadata($user, $videoId, $metadata);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error' => $result['error'] ?? null,
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Video metadata processed successfully (Server Key)',
            'data' => [
                'video' => new VideoResource($result['data']['video']),
                'videoId' => $result['data']['videoId'],
            ],
        ]);
    }

    /**
     * Process video metadata after client-side upload to BunnyCDN
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function processMetadata(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'video_id' => 'required|string',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'tags' => 'nullable|array',
            'team_tags' => 'nullable|array',
            'is_private' => 'nullable|boolean',
            'is_commentable' => 'nullable|boolean',
            'category' => 'nullable|string',
            'location' => 'nullable|string',
            'language' => 'nullable|string',
            'content_rating' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $videoId = $request->input('video_id');
        $metadata = $request->all();

        $result = $this->videoService->processVideoMetadata($user, $videoId, $metadata);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error' => $result['error'] ?? null,
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Video metadata processed successfully',
            'data' => [
                'video' => new VideoResource($result['data']['video']),
                'videoId' => $result['data']['videoId'],
                'thumbnailUrl' => $result['data']['thumbnailUrl'],
            ],
        ]);
    }

    /**
     * Like or unlike a video
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleLike(Request $request, $id)
    {
        $video = Video::find($id);

        if (!$video) {
            return response()->json([
                'success' => false,
                'message' => 'Video not found',
            ], 404);
        }

        $user = $request->user();
        $existingLike = VideoLike::where('user_id', $user->id)
            ->where('video_id', $video->id)
            ->first();

        if ($existingLike) {
            // Unlike
            \App\Jobs\StoreVideoUnlike::dispatch($existingLike->id);
            $action = 'unliked';
        } else {
            // Like
            \App\Jobs\StoreVideoLike::dispatch([
                'user_id' => $user->id,
                'video_id' => $video->id,
            ]);

            // Track user interaction for likes
            app(\App\Services\VideoService::class)->trackUserInteraction(
                $video->id,
                $user->id,
                'like',
                true // Update scores
            );

            $action = 'liked';
        }

        // Clear cache for this video
        Cache::forget("video:{$video->id}");

        return response()->json([
            'success' => true,
            'message' => "Video {$action} successfully",
            'data' => [
                'action' => $action,
                'likes_count' => $video->video_likes()->count(),
            ],
        ]);
    }

    /**
     * Add a comment to a video
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function addComment(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'comment' => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:video_comments,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $video = Video::find($id);

        if (!$video) {
            return response()->json([
                'success' => false,
                'message' => 'Video not found',
            ], 404);
        }

        if (!$video->is_commentable) {
            return response()->json([
                'success' => false,
                'message' => 'Comments are disabled for this video',
            ], 403);
        }

        $user = $request->user();
        $comment = VideoComment::create([
            'user_id' => $user->id,
            'video_id' => $video->id,
            'parent_id' => $request->input('parent_id'),
            'comment' => $request->input('comment'),
        ]);

        // Track user interaction for comments
        app(\App\Services\VideoService::class)->trackUserInteraction(
            $video->id,
            $user->id,
            'comment',
            true // Update scores
        );

        // Update engagement score is now handled by trackUserInteraction

        // Clear cache for this video
        Cache::forget("video:{$video->id}");

        return response()->json([
            'success' => true,
            'message' => 'Comment added successfully',
            'data' => [
                'comment' => $comment->load('user'),
            ],
        ]);
    }

    /**
     * Report a video
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function reportVideo(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:1000',
            'category' => 'required|string|in:inappropriate,copyright,violence,harassment,other',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $video = Video::find($id);

        if (!$video) {
            return response()->json([
                'success' => false,
                'message' => 'Video not found',
            ], 404);
        }

        $user = $request->user();

        // Store the report
        $report = $video->reports()->create([
            'user_id' => $user->id,
            'reason' => $request->input('reason'),
            'category' => $request->input('category'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Video reported successfully',
            'data' => [
                'report_id' => $report->id,
            ],
        ]);
    }

    /**
     * Delete a video
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        $video = Video::find($id);

        if (!$video) {
            return response()->json([
                'success' => false,
                'message' => 'Video not found',
            ], 404);
        }

        $user = $request->user();

        // Check if the user is authorized to delete the video
        if ($user->id !== $video->user_id && !$user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to delete this video',
            ], 403);
        }

        try {
            // Delete from BunnyCDN
            if ($video->video_guid) {
                $this->bunnyService->deleteVideo($video->video_guid);
            }

            // Delete from database
            $video->delete();

            return response()->json([
                'success' => true,
                'message' => 'Video deleted successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete video: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'video_id' => $video->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete video: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get videos for a specific user's profile
     *
     * @param Request $request
     * @param int|null $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserVideos(Request $request, $userId = null)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        // If no userId is provided, use the authenticated user
        if (!$userId) {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], 401);
            }
            $userId = $user->id;
        }

        // Check if the requested user exists
        $targetUser = User::find($userId);
        if (!$targetUser) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        // Get the authenticated user (if any)
        $currentUser = $request->user();

        // Build query based on permissions
        $query = Video::where('user_id', $userId);

        // If viewing someone else's profile and not an admin, only show public videos
        if (!$currentUser || ($currentUser->id !== $targetUser->id && !$currentUser->hasRole('admin'))) {
            $query->where('is_private', false);
        }

        // Apply sorting
        $query->orderBy('created_at', 'desc');

        // Get paginated results
        $videos = $query->with(['user'])
            ->withCount('video_likes')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => VideoResource::collection($videos),
            'meta' => [
                'current_page' => $videos->currentPage(),
                'last_page' => $videos->lastPage(),
                'per_page' => $videos->perPage(),
                'total' => $videos->total(),
                'user' => [
                    'id' => $targetUser->id,
                    'name' => $targetUser->name,
                    'username' => $targetUser->username,
                    'profile_photo' => $targetUser->profile_photo_url,
                ],
            ],
        ]);
    }
}
