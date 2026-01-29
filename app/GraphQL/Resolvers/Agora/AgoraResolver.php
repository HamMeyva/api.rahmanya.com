<?php

namespace App\GraphQL\Resolvers\Agora;

use Exception;
use App\Models\GiftBasket;
use App\Services\AgoraService;
use App\Services\BunnyCdnService;
use App\Models\Agora\AgoraChannel;
use App\Models\LiveStreamCategory;
use App\Jobs\CheckStreamsHeartbeat;
use App\Services\AgoraTokenService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Agora\AgoraChannelViewer;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Services\LiveStream\AgoraChannelService;
use App\Services\LiveStream\LiveStreamGiftService;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use App\GraphQL\Traits\ChecksRedCardPunishment;

class AgoraResolver
{
    use ChecksRedCardPunishment;

    protected AgoraChannelService $agoraChannelService;
    protected LiveStreamGiftService $liveStreamGiftService;
    protected BunnyCdnService $bunnyCdnService;
    protected AgoraService $agoraService;

    /**
     * Default period for analytics (in days)
     */
    const DEFAULT_ANALYTICS_PERIOD = 30;

    public function __construct(
        AgoraChannelService $agoraChannelService,
        AgoraService $agoraService,
        LiveStreamGiftService $liveStreamGiftService,
        BunnyCdnService $bunnyCdnService
    ) {
        $this->agoraChannelService = $agoraChannelService;
        $this->agoraService = $agoraService;
        $this->liveStreamGiftService = $liveStreamGiftService;
        $this->bunnyCdnService = $bunnyCdnService;
    }

    /**
     * Get user stream statistics
     *
     * @param  null  $rootValue
     * @param  array  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext|null  $context
     * @param  \GraphQL\Type\Definition\ResolveInfo|null  $resolveInfo
     * @return array
     */
    public function userStreamStats($rootValue, array $args, GraphQLContext $context = null, ResolveInfo $resolveInfo = null)
    {
        try {
            // Get authenticated user
            $user = $context->user();

            // Determine which user's stats to fetch
            $targetUserId = $args['userId'] ?? $user->id;

            // Check permissions - only allow users to see their own stats unless they're admins
            if ($targetUserId != $user->id) {
                throw new Exception('You do not have permission to view these statistics');
            }

            // Default period is last 30 days
            $endDate = now();
            $startDate = now()->subDays(self::DEFAULT_ANALYTICS_PERIOD);

            // Get all streams for this user in the period
            $streams = AgoraChannel::where('user_id', $targetUserId)
                ->where('created_at', '>=', $startDate)
                ->where('created_at', '<=', $endDate)
                ->get();

            // Initialize analytics data
            $analytics = [
                'user_id' => $targetUserId,
                'total_streams' => $streams->count(),
                'total_stream_duration' => 0,
                'total_viewers' => 0,
                'max_concurrent_viewers' => 0,
                'total_likes' => 0,
                'total_messages' => 0,
                'total_gifts' => 0,
                'total_coins_earned' => 0,
                'avg_viewer_duration' => 0,
                'period' => self::DEFAULT_ANALYTICS_PERIOD . ' days',
                'start_date' => $startDate,
                'end_date' => $endDate
            ];

            // If no streams, return empty stats
            if ($streams->isEmpty()) {
                return $analytics;
            }

            // Calculate statistics from streams
            $totalViewerDuration = 0;
            $viewerCount = 0;

            foreach ($streams as $stream) {
                // Calculate stream duration in minutes
                $duration = 0;
                if ($stream->end_time) {
                    $startTime = $stream->start_time ?? $stream->created_at;
                    $duration = $startTime->diffInMinutes($stream->end_time);
                } elseif ($stream->start_time) {
                    // For ongoing streams, calculate duration until now
                    $duration = $stream->start_time->diffInMinutes(now());
                }

                $analytics['total_stream_duration'] += $duration;

                // Update max concurrent viewers if higher
                $analytics['max_concurrent_viewers'] = max($analytics['max_concurrent_viewers'], $stream->max_viewers ?? 0);

                // Add viewers, likes, messages, gifts
                $analytics['total_viewers'] += $stream->unique_viewers ?? 0;
                $analytics['total_likes'] += $stream->likes_count ?? 0;
                $analytics['total_messages'] += $stream->messages_count ?? 0;
                $analytics['total_gifts'] += $stream->gifts_count ?? 0;
                $analytics['total_coins_earned'] += $stream->coins_earned ?? 0;

                // For average viewer duration calculation
                if ($stream->viewer_duration_sum && $stream->unique_viewers) {
                    $totalViewerDuration += $stream->viewer_duration_sum;
                    $viewerCount += $stream->unique_viewers;
                }
            }

            // Calculate average viewer duration if we have data
            if ($viewerCount > 0) {
                $analytics['avg_viewer_duration'] = $totalViewerDuration / $viewerCount;
            }

            return $analytics;
        } catch (Exception $e) {
            Log::error('Error in userStreamStats: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * List all active live streams, sorted by viewer count
     *
     * @param  null  $rootValue
     * @param  array  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @param  \GraphQL\Type\Definition\ResolveInfo  $resolveInfo
     * @return array
     */
    public function listLiveStreams($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        try {
            $user = $context->user();
            Log::info('listLiveStreams called', [
                'user_id' => $user->id
            ]);

            // Get all active livestreams
            // Execute query and return paginated results
            // Use service method directly to ensure consistent logic
            $filters = [
                'page' => $args['page'] ?? 1,
                'limit' => $args['limit'] ?? 20,
                'order_by' => 'viewer_count',
                'order_dir' => 'desc',
                'include_cohost_streams' => true // Varsayılan olarak cohost yayınları da listede görünür
            ];

            Log::info('DEBUG: listLiveStreams calling getActiveStreams', ['filters' => $filters]);
            $result = $this->agoraChannelService->getActiveStreams($filters)->items();
            Log::info('DEBUG: listLiveStreams returned items count: ' . count($result));

            return $result;
        } catch (Exception $e) {
            Log::error('Error in listLiveStreams: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Advanced live streams query with filtering options
     *
     * @param  null  $rootValue
     * @param  array  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @param  \GraphQL\Type\Definition\ResolveInfo  $resolveInfo
     * @return array
     */
    public function liveStreams($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        try {
            $user = $context->user();
            Log::info('liveStreams called with filters', [
                'user_id' => $user->id,
                'args' => $args
            ]);

            // Base query for active livestreams
            $query = AgoraChannel::where('status_id', AgoraChannel::STATUS_LIVE)
                ->where('is_online', true);

            // Apply search filter if provided
            if (isset($args['search']) && !empty($args['search'])) {
                $search = $args['search'];
                $query->where(function (Builder $q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Apply category filter if provided
            if (isset($args['category_id']) && !empty($args['category_id'])) {
                $query->where('category_id', $args['category_id']);
            }

            // Filter by followed users only
            if (isset($args['followed_only']) && $args['followed_only']) {
                $followingIds = $user->following()->pluck('following_id')->toArray();
                $query->whereIn('user_id', $followingIds);
            }

            // Filter by featured streams only
            if (isset($args['featured_only']) && $args['featured_only']) {
                $query->where('is_featured', true);
            }

            // Default sorting by viewer count (most popular first)
            $query->orderBy('viewer_count', 'desc');

            // Pagination parameters
            $page = $args['page'] ?? 1;
            $limit = $args['limit'] ?? 20;

            Log::info('Executing filtered livestreams query');

            // Execute query and return paginated results
            return $query->paginate($limit, ['*'], 'page', $page)->items();
        } catch (\Exception $e) {
            Log::error('Error in liveStreams: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            throw $e;
        }
    }

    public function startStream($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $authUser = Auth::user();

        // Check for red card punishment
        $this->checkRedCardPunishment($authUser->id);

        $input = isset($args['input']) ? $args['input'] : $args;

        $validator = Validator::make($input, [
            'title' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:255',
            'language_id' => 'nullable|exists:languages,id',
            'category_id' => 'nullable|exists:live_stream_categories,id',
            'thumbnail_url' => 'nullable|string|max:255',
            'thumbnail_image' => 'nullable|string',  // Base64 encoded image data
            'thumbnail_extension' => 'nullable|string|in:jpg,jpeg,png',  // Image extension
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        try {
            // Kategori ID'sini kontrol et
            if (!empty($input['category_id'])) {
                Log::info('Kategori ID kontrol ediliyor', [
                    'category_id' => $input['category_id'],
                    'type' => gettype($input['category_id']),
                ]);

                // Kategori var mı kontrol et
                $category = LiveStreamCategory::find($input['category_id']);
                if ($category) {
                    Log::info('Kategori bulundu', [
                        'category_id' => $category->id,
                        'category_name' => $category->name,
                    ]);
                } else {
                    Log::warning('Kategori bulunamadı', [
                        'category_id' => $input['category_id'],
                    ]);
                }
            }

            // Görüntü yükleme işlemi
            if (!empty($input['thumbnail_image']) && !empty($input['thumbnail_extension'])) {
                try {
                    Log::info('Görüntü yükleme işlemi başlatılıyor', [
                        'extension' => $input['thumbnail_extension'],
                        'image_length' => strlen($input['thumbnail_image']),
                    ]);

                    // Temizlenmiş base64 verisini al ("data:image/jpeg;base64," gibi ön ekleri kaldır)
                    $base64Image = $input['thumbnail_image'];
                    if (strpos($base64Image, ';base64,') !== false) {
                        $base64Image = explode(';base64,', $base64Image)[1];
                        Log::info('Base64 ön eki temizlendi');
                    }

                    // Sanitize the base64 string to ensure it only contains valid base64 characters
                    $base64Image = preg_replace('/[^A-Za-z0-9\+\/\=]/', '', $base64Image);

                    // Ensure the base64 string length is valid (multiple of 4)
                    $base64Image = str_pad($base64Image, strlen($base64Image) + (4 - strlen($base64Image) % 4) % 4, '=');

                    // Decode base64 image with strict mode
                    $imageData = base64_decode($base64Image, true);

                    if ($imageData === false) {
                        Log::error('Geçersiz base64 görüntü verisi');
                        throw new \Exception('Invalid base64 image data');
                    }

                    Log::info('Base64 görüntü başarıyla decode edildi', [
                        'decoded_length' => strlen($imageData),
                        'file_size' => strlen($imageData),
                    ]);

                    // Upload image using BunnyCdnService
                    $mediaResult = $this->bunnyCdnService->uploadMedia(
                        $imageData,
                        uniqid('stream_', true),
                        $input['thumbnail_extension'],
                        $authUser->id,
                        'stream'
                    );

                    // Set the thumbnail_url in input
                    $input['thumbnail_url'] = $mediaResult['url'];

                    // Log successful upload
                    Log::info('Görüntü başarıyla yüklendi', [
                        'thumbnail_url' => $input['thumbnail_url'],
                    ]);
                } catch (Exception $e) {
                    Log::error('Görüntü yükleme hatası: ' . $e->getMessage(), [
                        'exception' => get_class($e),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    // Continue without thumbnail if upload fails
                    // We don't want to block stream creation just because thumbnail upload failed
                    $input['thumbnail_url'] = null;
                }
            } else {
                Log::info('Görüntü verisi bulunamadı, thumbnail_url null olarak ayarlandı');
                $input['thumbnail_url'] = null;
            }

            $agoraChannel = $this->agoraChannelService->startStream($authUser, $input);

            return [
                'success' => true,
                'message' => 'Yayın başlatıldı.',
                'token' => $agoraChannel->token ?? null,
                'channel_name' => $agoraChannel->channel_name ?? null,
                'stream_key' => $agoraChannel->stream_key ?? null,
                'agora_channel_id' => $agoraChannel->id,
                'agora_uid' => $authUser->agora_uid
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function streamHeartbeat($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = $context->user();
        $streamId = $args['stream_id'];
        $stream = AgoraChannel::findOrFail($streamId);

        try {
            $this->agoraChannelService->streamHeartbeat($stream, $user);

            return [
                'success' => true,
            ];
        } catch (Exception $e) {
            Log::error("Heartbeat failed: ", [
                'stream_id' => $streamId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
            ];
        }
    }

    /**
     * Update an existing stream
     */
    public function updateStream($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = $context->user();
        $streamId = $args['streamId'];

        $data = array_filter([
            'title' => $args['title'] ?? null,
            'description' => $args['description'] ?? null,
            'category_id' => $args['category_id'] ?? null,
            'thumbnail_url' => $args['thumbnail_url'] ?? null,
            'settings' => $args['settings'] ?? null
        ]);

        return $this->agoraChannelService->updateChannel($user, $streamId, $data);
    }

    /**
     * Start a live stream (offline -> online)
     */
    public function goLive($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = $context->user();
        $streamId = $args['streamId'];

        // Find the stream
        $stream = AgoraChannel::findOrFail($streamId);

        if ($stream->user_id !== $user->id) {
            throw new Exception('You are not authorized to manage this stream');
        }

        // Call the service method and check if successful
        $success = $this->agoraChannelService->goLive($stream);

        if (!$success) {
            throw new Exception('Failed to start live stream');
        }

        // Refresh the stream from database to get updated data
        $stream->refresh();

        return $stream;
    }

    /**
     * End a stream
     */
    public function endStream($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        try {
            $authUser = $context->user();

            /** @var \App\Models\Agora\AgoraChannel $agoraChannel */
            $agoraChannel = AgoraChannel::find($args['agora_channel_id']);
            if (!$agoraChannel) {
                return [
                    'success' => false,
                    'message' => 'Yayın bulunamadı.',
                ];
            }

            if ($agoraChannel->user_id != $authUser->id) {
                return [
                    'success' => false,
                    'message' => 'Yayını kapatma yetkiniz yok.',
                ];
            }

            $this->agoraChannelService->endStream($agoraChannel);

            return [
                'success' => true,
                'message' => 'Yayın sonlandırıldı.',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Join a stream as a viewer
     */
    public function joinStream($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $authUser = Auth::user();
        $streamId = $args['agora_channel_id'];

        /** @var \App\Models\Agora\AgoraChannel $agoraChannel */
        $agoraChannel = AgoraChannel::find($streamId);
        if (!$agoraChannel || !$agoraChannel->is_online) {
            return [
                'success' => false,
                'message' => 'Yayın sonlanmış, diğer yayınlara göz atabilirsiniz.',
            ];
        }

        try {
            $viewer = $this->agoraChannelService->joinStream($agoraChannel, $authUser, AgoraChannelViewer::ROLE_VIEWER, AgoraTokenService::RoleSubscriber);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }

        // Cohost stream için doğru channel_name'i döndür
        $channelNameToReturn = $agoraChannel->channel_name;
        $parentChannelName = null;

        Log::info('JoinStream: Processing stream', [
            'stream_id' => $agoraChannel->id,
            'channel_name' => $agoraChannel->channel_name,
            'is_cohost_stream' => $agoraChannel->is_cohost_stream,
            'parent_channel_id' => $agoraChannel->parent_channel_id
        ]);

        if ($agoraChannel->is_cohost_stream && $agoraChannel->parent_channel_id) {
            $parentStream = AgoraChannel::find($agoraChannel->parent_channel_id);
            if ($parentStream) {
                // CRITICAL: For Zego, mixed stream ID uses the parent's stream ID, not channel_name
                // Mixed stream format is: {parent_stream_id}_mixed
                $parentChannelName = $parentStream->id; // Use parent's ID for mixed stream
                $channelNameToReturn = $parentStream->id; // Return parent's ID

                Log::info('Cohost viewer: Using parent ID for mixed stream', [
                    'cohost_stream_id' => $agoraChannel->id,
                    'cohost_channel_name' => $agoraChannel->channel_name,
                    'parent_stream_id' => $parentStream->id,
                    'parent_channel_name' => $parentStream->channel_name,
                    'expected_mixed_stream' => $parentStream->id . '_mixed'
                ]);
            } else {
                Log::warning('Parent stream not found for cohost stream', [
                    'cohost_stream_id' => $agoraChannel->id,
                    'parent_channel_id' => $agoraChannel->parent_channel_id
                ]);
            }
        }

        return [
            'success' => true,
            'message' => 'Yayına katıldınız.',
            'token' => $viewer->token,
            'channel_name' => $channelNameToReturn,
            'agora_channel_id' => $agoraChannel->id,
            'is_cohost_stream' => $agoraChannel->is_cohost_stream,
            'parent_channel_id' => $agoraChannel->parent_channel_id,
            'parent_channel_name' => isset($parentStream) ? $parentStream->id : null, // Return parent's ID, not channel_name
        ];
    }

    public function leaveStream($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        try {
            $user = $context->user();
            $streamId = $args['agora_channel_id'];

            /** @var \App\Models\Agora\AgoraChannel $stream */
            $stream = AgoraChannel::find($streamId);
            if (!$stream) {
                return [
                    'success' => false,
                    'message' => 'Yayın bulunamadı.',
                ];
            }

            $this->agoraChannelService->leaveStream($stream, $user);

            return [
                'success' => true,
                'message' => 'Yayından ayrıldınız.'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function likeStream($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        try {
            $user = $context->user();
            \Log::info('🔴 AgoraResolver: likeStream called for channel: ' . $args['agora_channel_id'] . ' by user: ' . $user->id);

            /** @var AgoraChannel $stream */
            $stream = AgoraChannel::find($args['agora_channel_id']);
            if (!$stream) {
                \Log::info('❌ AgoraResolver: Stream not found: ' . $args['agora_channel_id']);
                return [
                    'success' => false,
                    'message' => 'Yayın bulunamadı.',
                ];
            }

            \Log::info('✅ AgoraResolver: Stream found, calling service likeStream');
            $this->agoraChannelService->likeStream($stream, $user);
            \Log::info('✅ AgoraResolver: Service likeStream completed successfully');

            return [
                'success' => true,
                'message' => 'Yayını beğendiniz.'
            ];
        } catch (Exception $e) {
            \Log::error('❌ AgoraResolver: likeStream error: ' . $e->getMessage());
            \Log::error('❌ Stack trace: ' . $e->getTraceAsString());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function streamScreenShoot($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $validator = Validator::make($args, [
            'agora_channel_id' => 'required',
            'media' => 'required|image|mimes:jpeg,png,jpg|max:10240',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        try {
            $user = $context->user();

            /** @var AgoraChannel $stream */
            $stream = AgoraChannel::find($args['agora_channel_id']);
            if (!$stream)
                throw new Exception('Yayın bulunamadı.');

            if ($stream->user_id != $user->id)
                throw new Exception('Yayını ekran görüntüsü alamazsınız.');


            $media = $args['media'];
            $this->agoraChannelService->screenShoot($stream, $media);

            return [
                'success' => true,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function categorySubcategories($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        // Sorgu oluştur
        $query = LiveStreamCategory::query()
            ->active()
            ->byParent($args['categoryId'])
            ->ordered();

        // Sayfalama parametrelerini al
        $page = $args['page'] ?? 1;
        $limit = $args['limit'] ?? 10;

        // Sorguyu çalıştır ve sonuçları döndür
        return $query->paginate($limit, ['*'], 'page', $page)->items();
    }

    public function liveStreamCategories($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        try {
            $query = LiveStreamCategory::query()
                ->active()
                ->ordered();


            // Üst kategoriye göre filtrele
            if (isset($args['parent_id'])) {
                $query->byParent($args['parent_id']);
            } else {
                $query->mainCategories();
            }

            // Arama filtresi
            if (isset($args['search']) && !empty($args['search'])) {
                $search = $args['search'];
                $query->where(function (Builder $query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
                Log::info('Applying search filter: ' . $search);
            }

            // Sayfalama parametrelerini al
            $page = $args['page'] ?? 1;
            $limit = $args['limit'] ?? 10;


            // Sorguyu çalıştır ve sonuçları döndür
            $results = $query->paginate($limit, ['*'], 'page', $page)->items();

            return $results;
        } catch (Exception $e) {
            Log::error('Error in liveStreamCategories: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Bir kullanıcının yayınlarını getir
     *
     * @param  null  $rootValue
     * @param  array  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @param  \GraphQL\Type\Definition\ResolveInfo  $resolveInfo
     * @return array
     */
    public function userStreams($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        try {
            Log::info('userStreams called', [
                'userId' => $args['userId']
            ]);

            // Sorgu oluştur
            $query = AgoraChannel::query()
                ->where('user_id', $args['userId'])
                ->orderBy('created_at', 'desc');

            // Sayfalama parametrelerini al
            $page = $args['page'] ?? 1;
            $limit = $args['limit'] ?? 10;

            // Sorguyu çalıştır ve sonuçları döndür
            return $query->paginate($limit, ['*'], 'page', $page)->items();
        } catch (\Exception $e) {
            Log::error('Error in userStreams: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Takip edilen kullanıcıların canlı yayınlarını getir
     *
     * @param  null  $rootValue
     * @param  array  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @param  \GraphQL\Type\Definition\ResolveInfo  $resolveInfo
     * @return array
     */
    public function followingLiveStreams($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        try {
            $user = $context->user();
            Log::info('followingLiveStreams called', [
                'user_id' => $user->id
            ]);

            // Kullanıcının takip ettiği kullanıcıların ID'lerini al
            $followingIds = \App\Models\Follow::where('follower_id', $user->id)
                ->where('status', 'accepted')
                ->whereNull('deleted_at')  // Soft-deleted olmayan kayıtları al
                ->pluck('followed_id')
                ->toArray();

            Log::info('Following user IDs', [
                'count' => count($followingIds),
                'ids' => $followingIds
            ]);

            // Takip edilen kullanıcıların aktif yayınlarını sorgula
            $query = AgoraChannel::whereIn('user_id', $followingIds)
                ->where('status_id', AgoraChannel::STATUS_LIVE)
                ->where('is_online', true)
                ->orderBy('viewer_count', 'desc'); // İzleyici sayısına göre sırala

            // Sayfalama parametrelerini al
            $page = $args['page'] ?? 1;
            $limit = $args['limit'] ?? 20;

            // Sorguyu çalıştır ve sonuçları döndür
            $streams = $query->paginate($limit, ['*'], 'page', $page)->items();

            Log::info('Found following live streams', [
                'count' => count($streams)
            ]);

            return $streams;
        } catch (\Exception $e) {
            Log::error('Error in followingLiveStreams: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Bir kategorideki yayınları getir
     *
     * @param  null  $rootValue
     * @param  array  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @param  \GraphQL\Type\Definition\ResolveInfo  $resolveInfo
     * @return array
     */
    public function categoryStreams($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        // Sorgu oluştur
        $query = AgoraChannel::query()
            ->where('category_id', $args['categoryId'])
            ->where('status_id', AgoraChannel::STATUS_LIVE)
            ->orderBy('created_at', 'desc');

        // Sayfalama parametrelerini al
        $page = $args['page'] ?? 1;
        $limit = $args['limit'] ?? 10;

        // Sorguyu çalıştır ve sonuçları döndür
        return $query->paginate($limit, ['*'], 'page', $page)->items();
    }

    /**
     * Bir yayının sabitlenmiş mesajını getir
     *
     * @param  null  $rootValue
     * @param  array  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @param  \GraphQL\Type\Definition\ResolveInfo  $resolveInfo
     * @return \App\Models\Agora\AgoraChannelMessage|null
     */
    public function pinnedMessage($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        return \App\Models\Agora\AgoraChannelMessage::query()
            ->where('agora_channel_id', $args['streamId'])
            ->where('is_pinned', true)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Get the current viewer count for a stream
     *
     * @param  null  $rootValue
     * @param  array  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @param  \GraphQL\Type\Definition\ResolveInfo  $resolveInfo
     * @return int
     */
    public function streamViewerCount($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        try {
            Log::info('streamViewerCount called', [
                'streamId' => $args['streamId'],
                'user_id' => $context->user()->id
            ]);

            $validator = Validator::make($args, [
                'streamId' => 'required|exists:agora_channels,id',
            ]);

            if ($validator->fails()) {
                Log::warning('streamViewerCount validation failed', [
                    'errors' => $validator->errors()->toArray()
                ]);
                throw ValidationException::withMessages($validator->errors()->toArray());
            }

            $count = AgoraChannelViewer::where('agora_channel_id', $args['streamId'])
                ->where('status', 'active')
                ->count();

            Log::info('streamViewerCount result', [
                'streamId' => $args['streamId'],
                'count' => $count
            ]);

            return $count;
        } catch (\Exception $e) {
            Log::error('Error in streamViewerCount: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Get the current user's viewer status for a stream
     *
     * @param  null  $rootValue
     * @param  array  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @param  \GraphQL\Type\Definition\ResolveInfo  $resolveInfo
     * @return \App\Models\Agora\AgoraChannelViewer|null
     */
    public function myViewerStatus($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        try {
            $user = $context->user();
            Log::info('myViewerStatus called', [
                'streamId' => $args['streamId'],
                'user_id' => $user->id
            ]);

            $validator = Validator::make($args, [
                'streamId' => 'required|exists:agora_channels,id',
            ]);

            if ($validator->fails()) {
                Log::warning('myViewerStatus validation failed', [
                    'errors' => $validator->errors()->toArray()
                ]);
                throw ValidationException::withMessages($validator->errors()->toArray());
            }

            $viewerStatus = AgoraChannelViewer::where('agora_channel_id', $args['streamId'])
                ->where('user_id', $user->id)
                ->first();

            Log::info('myViewerStatus result', [
                'streamId' => $args['streamId'],
                'user_id' => $user->id,
                'status' => $viewerStatus ? $viewerStatus->status : 'not found'
            ]);

            return $viewerStatus;
        } catch (\Exception $e) {
            Log::error('Error in myViewerStatus: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Get analytics for a specific stream
     *
     * @param  null  $rootValue
     * @param  array  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @param  \GraphQL\Type\Definition\ResolveInfo  $resolveInfo
     * @return array
     */
    public function streamAnalytics($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        try {
            $user = $context->user();
            Log::info('streamAnalytics called', [
                'streamId' => $args['streamId'],
                'user_id' => $user->id
            ]);

            $validator = Validator::make($args, [
                'streamId' => 'required|exists:agora_channels,id',
            ]);

            if ($validator->fails()) {
                Log::warning('streamAnalytics validation failed', [
                    'errors' => $validator->errors()->toArray()
                ]);
                throw ValidationException::withMessages($validator->errors()->toArray());
            }

            // Get the stream
            $stream = AgoraChannel::findOrFail($args['streamId']);

            // Check permissions (only stream owner or admin can see analytics)
            if ($user->id !== $stream->user_id && !$user->hasRole('admin')) {
                Log::warning('Unauthorized access to streamAnalytics', [
                    'streamId' => $args['streamId'],
                    'user_id' => $user->id,
                    'stream_owner_id' => $stream->user_id
                ]);
                throw new Exception('You are not authorized to view analytics for this stream');
            }

            // Calculate analytics data
            $totalViewers = AgoraChannelViewer::where('agora_channel_id', $args['streamId'])->count();
            $maxConcurrentViewers = $stream->max_viewer_count;
            $totalLikes = $stream->total_likes;
            $totalMessages = $stream->total_messages;
            $totalGifts = $stream->total_gifts ?? 0;
            $totalCoinsEarned = $stream->total_coins_earned ?? 0;

            // Calculate stream duration if available
            $streamDuration = 0;
            if ($stream->started_at && $stream->ended_at) {
                $start = new \DateTime($stream->started_at);
                $end = new \DateTime($stream->ended_at);
                $streamDuration = $end->getTimestamp() - $start->getTimestamp();
            } elseif ($stream->started_at && $stream->status === 'online') {
                $start = new \DateTime($stream->started_at);
                $end = new \DateTime();
                $streamDuration = $end->getTimestamp() - $start->getTimestamp();
            }

            // Calculate average viewer duration if possible
            $avgViewerDuration = 0;
            if ($totalViewers > 0) {
                $totalDuration = AgoraChannelViewer::where('agora_channel_id', $args['streamId'])
                    ->sum('duration');
                $avgViewerDuration = $totalDuration / $totalViewers;
            }

            Log::info('streamAnalytics result', [
                'streamId' => $args['streamId'],
                'totalViewers' => $totalViewers,
                'maxConcurrentViewers' => $maxConcurrentViewers,
                'streamDuration' => $streamDuration
            ]);

            // Return analytics data
            return [
                'user_id' => $stream->user_id,
                'total_streams' => 1,
                'total_stream_duration' => $streamDuration,
                'total_viewers' => $totalViewers,
                'max_concurrent_viewers' => $maxConcurrentViewers,
                'total_likes' => $totalLikes,
                'total_messages' => $totalMessages,
                'total_gifts' => $totalGifts,
                'total_coins_earned' => $totalCoinsEarned,
                'avg_viewer_duration' => $avgViewerDuration,
                'period' => 'single_stream',
                'start_date' => $stream->started_at,
                'end_date' => $stream->ended_at
            ];
        } catch (\Exception $e) {
            Log::error('Error in streamAnalytics: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Get performance summary for a user over a specific time period
     *
     * @param  null  $rootValue
     * @param  array  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @param  \GraphQL\Type\Definition\ResolveInfo  $resolveInfo
     * @return array
     */
    public function performanceSummary($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        try {
            $user = $context->user();
            Log::info('performanceSummary called', [
                'user_id' => $user->id,
                'args' => $args
            ]);

            // Default to the authenticated user if userId not provided
            $userId = $args['userId'] ?? $user->id;

            // Check permissions (only self or admin can see performance summary)
            if ($userId !== $user->id && !$user->hasRole('admin')) {
                Log::warning('Unauthorized access to performanceSummary', [
                    'requested_user_id' => $userId,
                    'user_id' => $user->id
                ]);
                throw new Exception('You are not authorized to view performance summary for this user');
            }

            // Set default period to 'month' if not provided
            $period = $args['period'] ?? 'month';

            // Calculate date range based on period
            $endDate = new \DateTime();
            $startDate = new \DateTime();

            switch ($period) {
                case 'day':
                    $startDate->modify('-1 day');
                    break;
                case 'week':
                    $startDate->modify('-7 days');
                    break;
                case 'month':
                    $startDate->modify('-30 days');
                    break;
                case 'year':
                    $startDate->modify('-365 days');
                    break;
                default:
                    $startDate->modify('-30 days'); // Default to month
            }

            // Query streams within this period for the user
            $streams = AgoraChannel::where('user_id', $userId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            // Calculate aggregate data
            $totalStreams = $streams->count();
            $totalStreamDuration = 0;
            $totalViewers = 0;
            $maxConcurrentViewers = 0;
            $totalLikes = 0;
            $totalMessages = 0;
            $totalGifts = 0;
            $totalCoinsEarned = 0;

            foreach ($streams as $stream) {
                // Calculate stream duration
                if ($stream->started_at && $stream->ended_at) {
                    $start = new \DateTime($stream->started_at);
                    $end = new \DateTime($stream->ended_at);
                    $totalStreamDuration += $end->getTimestamp() - $start->getTimestamp();
                }

                // Aggregate metrics
                $totalViewers += AgoraChannelViewer::where('agora_channel_id', $stream->id)->count();
                $maxConcurrentViewers = max($maxConcurrentViewers, $stream->max_viewer_count);
                $totalLikes += $stream->total_likes;
                $totalMessages += $stream->total_messages;
                $totalGifts += $stream->total_gifts ?? 0;
                $totalCoinsEarned += $stream->total_coins_earned ?? 0;
            }

            // Calculate average viewer duration if possible
            $avgViewerDuration = 0;
            if ($totalViewers > 0) {
                $totalDuration = AgoraChannelViewer::whereIn('agora_channel_id', $streams->pluck('id')->toArray())
                    ->sum('duration');
                $avgViewerDuration = $totalDuration / $totalViewers;
            }

            Log::info('performanceSummary result', [
                'user_id' => $userId,
                'period' => $period,
                'totalStreams' => $totalStreams,
                'totalStreamDuration' => $totalStreamDuration
            ]);

            // Return performance summary data
            return [
                'user_id' => $userId,
                'total_streams' => $totalStreams,
                'total_stream_duration' => $totalStreamDuration,
                'total_viewers' => $totalViewers,
                'max_concurrent_viewers' => $maxConcurrentViewers,
                'total_likes' => $totalLikes,
                'total_messages' => $totalMessages,
                'total_gifts' => $totalGifts,
                'total_coins_earned' => $totalCoinsEarned,
                'avg_viewer_duration' => $avgViewerDuration,
                'period' => $period,
                'start_date' => $startDate,
                'end_date' => $endDate
            ];
        } catch (\Exception $e) {
            Log::error('Error in performanceSummary: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Ban a viewer from a stream
     *
     * @param  null  $rootValue
     * @param  array  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @param  \GraphQL\Type\Definition\ResolveInfo  $resolveInfo
     * @return bool
     */
    public function banViewer($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        try {
            $user = $context->user();
            Log::info('banViewer called', [
                'streamId' => $args['streamId'],
                'viewerId' => $args['viewerId'],
                'user_id' => $user->id
            ]);

            $validator = Validator::make($args, [
                'streamId' => 'required|exists:agora_channels,id',
                'viewerId' => 'required|exists:users,id',
            ]);

            if ($validator->fails()) {
                Log::warning('banViewer validation failed', [
                    'errors' => $validator->errors()->toArray()
                ]);
                throw ValidationException::withMessages($validator->errors()->toArray());
            }

            // Get the stream
            $stream = AgoraChannel::findOrFail($args['streamId']);

            // Check if user is authorized (stream owner or moderator)
            $isAuthorized = $user->id === $stream->user_id ||
                AgoraChannelViewer::where('agora_channel_id', $stream->id)
                    ->where('user_id', $user->id)
                    ->where('is_moderator', true)
                    ->exists();

            if (!$isAuthorized) {
                Log::warning('Unauthorized access to banViewer', [
                    'streamId' => $args['streamId'],
                    'user_id' => $user->id,
                    'stream_owner_id' => $stream->user_id
                ]);
                throw new Exception('You are not authorized to ban viewers from this stream');
            }

            // Can't ban the stream owner
            if ($args['viewerId'] === $stream->user_id) {
                Log::warning('Attempt to ban stream owner', [
                    'streamId' => $args['streamId'],
                    'viewerId' => $args['viewerId']
                ]);
                throw new Exception('Cannot ban the stream owner');
            }

            // Find or create viewer record
            $viewer = AgoraChannelViewer::firstOrCreate(
                [
                    'agora_channel_id' => $args['streamId'],
                    'user_id' => $args['viewerId']
                ],
                [
                    'status' => 'active',
                    'is_moderator' => false,
                    'is_blocked' => false,
                    'last_activity_at' => now(),
                    'joined_at' => now()
                ]
            );

            // Update status to banned
            $viewer->status = 'banned';
            $viewer->is_blocked = true;
            $viewer->save();

            Log::info('Viewer banned successfully', [
                'streamId' => $args['streamId'],
                'viewerId' => $args['viewerId']
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error in banViewer: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Pin a message in a live stream chat
     *
     * @param  null  $rootValue
     * @param  array  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @param  \GraphQL\Type\Definition\ResolveInfo  $resolveInfo
     * @return bool
     */
    public function pinMessage($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        try {
            $user = $context->user();
            Log::info('pinMessage called', [
                'streamId' => $args['streamId'],
                'messageId' => $args['messageId'],
                'user_id' => $user->id
            ]);

            $validator = Validator::make($args, [
                'streamId' => 'required|exists:agora_channels,id',
                'messageId' => 'required|exists:agora_channel_messages,id',
            ]);

            if ($validator->fails()) {
                Log::warning('pinMessage validation failed', [
                    'errors' => $validator->errors()->toArray()
                ]);
                throw ValidationException::withMessages($validator->errors()->toArray());
            }

            // Get the stream
            $stream = AgoraChannel::findOrFail($args['streamId']);

            // Check if user is authorized (stream owner or moderator)
            $isAuthorized = $user->id === $stream->user_id ||
                AgoraChannelViewer::where('agora_channel_id', $stream->id)
                    ->where('user_id', $user->id)
                    ->where('is_moderator', true)
                    ->exists();

            if (!$isAuthorized) {
                Log::warning('Unauthorized access to pinMessage', [
                    'streamId' => $args['streamId'],
                    'user_id' => $user->id,
                    'stream_owner_id' => $stream->user_id
                ]);
                throw new Exception('You are not authorized to pin messages in this stream');
            }

            // First, unpin any previously pinned messages
            \App\Models\Agora\AgoraChannelMessage::where('agora_channel_id', $args['streamId'])
                ->where('is_pinned', true)
                ->update(['is_pinned' => false]);

            // Now pin the specified message
            $message = \App\Models\Agora\AgoraChannelMessage::where('id', $args['messageId'])
                ->where('agora_channel_id', $args['streamId'])
                ->first();

            if (!$message) {
                Log::warning('Message not found or not in this stream', [
                    'messageId' => $args['messageId'],
                    'streamId' => $args['streamId']
                ]);
                throw new Exception('Message not found or not in this stream');
            }

            $message->is_pinned = true;
            $message->save();

            Log::info('Message pinned successfully', [
                'messageId' => $args['messageId'],
                'streamId' => $args['streamId']
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error in pinMessage: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            throw $e;
        }
    }

    public function unpinMessage($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        try {
            $user = $context->user();
            Log::info('unpinMessage called', [
                'streamId' => $args['streamId'],
                'messageId' => $args['messageId'],
                'user_id' => $user->id
            ]);

            $validator = Validator::make($args, [
                'streamId' => 'required|exists:agora_channels,id',
                'messageId' => 'required|exists:agora_channel_messages,id',
            ]);

            if ($validator->fails()) {
                Log::warning('unpinMessage validation failed', [
                    'errors' => $validator->errors()->toArray()
                ]);
                throw ValidationException::withMessages($validator->errors()->toArray());
            }

            // Get the stream
            $stream = AgoraChannel::findOrFail($args['streamId']);

            // Check if user is authorized (stream owner or moderator)
            $isAuthorized = $user->id === $stream->user_id ||
                AgoraChannelViewer::where('agora_channel_id', $stream->id)
                    ->where('user_id', $user->id)
                    ->where('is_moderator', true)
                    ->exists();

            if (!$isAuthorized) {
                Log::warning('Unauthorized access to unpinMessage', [
                    'streamId' => $args['streamId'],
                    'user_id' => $user->id,
                    'stream_owner_id' => $stream->user_id
                ]);
                throw new Exception('You are not authorized to unpin messages in this stream');
            }

            // Unpin the specified message
            $message = \App\Models\Agora\AgoraChannelMessage::where('id', $args['messageId'])
                ->where('agora_channel_id', $args['streamId'])
                ->first();

            if (!$message) {
                Log::warning('Message not found or not in this stream', [
                    'messageId' => $args['messageId'],
                    'streamId' => $args['streamId']
                ]);
                throw new Exception('Message not found or not in this stream');
            }

            if (!$message->is_pinned) {
                Log::info('Message was not pinned', [
                    'messageId' => $args['messageId'],
                    'streamId' => $args['streamId']
                ]);
                return true; // Already unpinned
            }

            $message->is_pinned = false;
            $message->save();

            Log::info('Message unpinned successfully', [
                'messageId' => $args['messageId'],
                'streamId' => $args['streamId']
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error in unpinMessage: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Block a user from live stream chat
     *
     * @param  null  $rootValue
     * @param  array  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @param  \GraphQL\Type\Definition\ResolveInfo  $resolveInfo
     * @return bool
     */
    public function blockUserFromLiveStreamChat($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        try {
            $user = $context->user();
            Log::info('blockUserFromLiveStreamChat called', [
                'streamId' => $args['streamId'],
                'userId' => $args['userId'],
                'user_id' => $user->id
            ]);

            $validator = Validator::make($args, [
                'streamId' => 'required|exists:agora_channels,id',
                'userId' => 'required|exists:users,id',
            ]);

            if ($validator->fails()) {
                Log::warning('blockUserFromLiveStreamChat validation failed', [
                    'errors' => $validator->errors()->toArray()
                ]);
                throw ValidationException::withMessages($validator->errors()->toArray());
            }

            // Get the stream
            $stream = AgoraChannel::findOrFail($args['streamId']);

            // Check if user is authorized (stream owner or moderator)
            $isAuthorized = $user->id === $stream->user_id ||
                AgoraChannelViewer::where('agora_channel_id', $stream->id)
                    ->where('user_id', $user->id)
                    ->where('is_moderator', true)
                    ->exists();

            if (!$isAuthorized) {
                Log::warning('Unauthorized access to blockUserFromLiveStreamChat', [
                    'streamId' => $args['streamId'],
                    'user_id' => $user->id,
                    'stream_owner_id' => $stream->user_id
                ]);
                throw new Exception('You are not authorized to block users in this stream');
            }

            // Can't block the stream owner
            if ($args['userId'] === $stream->user_id) {
                Log::warning('Attempt to block stream owner', [
                    'streamId' => $args['streamId'],
                    'userId' => $args['userId']
                ]);
                throw new Exception('Cannot block the stream owner');
            }

            // Find or create viewer record
            $viewer = AgoraChannelViewer::firstOrCreate(
                [
                    'agora_channel_id' => $args['streamId'],
                    'user_id' => $args['userId']
                ],
                [
                    'status' => 'active',
                    'is_moderator' => false,
                    'is_blocked' => false,
                    'last_activity_at' => now(),
                    'joined_at' => now()
                ]
            );

            // Update blocked status
            $viewer->is_blocked = true;
            $viewer->save();

            Log::info('User blocked from chat successfully', [
                'streamId' => $args['streamId'],
                'userId' => $args['userId']
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error in blockUserFromLiveStreamChat: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Unblock a user from live stream chat
     *
     * @param  null  $rootValue
     * @param  array  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @param  \GraphQL\Type\Definition\ResolveInfo  $resolveInfo
     * @return bool
     */
    public function unblockUserFromLiveStreamChat($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        try {
            $user = $context->user();
            Log::info('unblockUserFromLiveStreamChat called', [
                'streamId' => $args['streamId'],
                'userId' => $args['userId'],
                'user_id' => $user->id
            ]);

            $validator = Validator::make($args, [
                'streamId' => 'required|exists:agora_channels,id',
                'userId' => 'required|exists:users,id',
            ]);

            if ($validator->fails()) {
                Log::warning('unblockUserFromLiveStreamChat validation failed', [
                    'errors' => $validator->errors()->toArray()
                ]);
                throw ValidationException::withMessages($validator->errors()->toArray());
            }

            // Get the stream
            $stream = AgoraChannel::findOrFail($args['streamId']);

            // Check if user is authorized (stream owner or moderator)
            $isAuthorized = $user->id === $stream->user_id ||
                AgoraChannelViewer::where('agora_channel_id', $stream->id)
                    ->where('user_id', $user->id)
                    ->where('is_moderator', true)
                    ->exists();

            if (!$isAuthorized) {
                Log::warning('Unauthorized access to unblockUserFromLiveStreamChat', [
                    'streamId' => $args['streamId'],
                    'user_id' => $user->id,
                    'stream_owner_id' => $stream->user_id
                ]);
                throw new Exception('You are not authorized to unblock users in this stream');
            }

            // Find viewer record
            $viewer = AgoraChannelViewer::where('agora_channel_id', $args['streamId'])
                ->where('user_id', $args['userId'])
                ->first();

            if (!$viewer) {
                // If there is no viewer record, they are implicitly not blocked
                return true;
            }

            // Update blocked status
            $viewer->is_blocked = false;
            $viewer->save();

            Log::info('User unblocked from chat successfully', [
                'streamId' => $args['streamId'],
                'userId' => $args['userId']
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error in unblockUserFromLiveStreamChat: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Add a moderator to a live stream
     *
     * @param  null  $rootValue
     * @param  array  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @param  \GraphQL\Type\Definition\ResolveInfo  $resolveInfo
     * @return bool
     */
    public function addModerator($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        try {
            $user = $context->user();
            Log::info('addModerator called', [
                'streamId' => $args['streamId'],
                'userId' => $args['userId'],
                'user_id' => $user->id
            ]);

            $validator = Validator::make($args, [
                'streamId' => 'required|exists:agora_channels,id',
                'userId' => 'required|exists:users,id',
            ]);

            if ($validator->fails()) {
                Log::warning('addModerator validation failed', [
                    'errors' => $validator->errors()->toArray()
                ]);
                throw ValidationException::withMessages($validator->errors()->toArray());
            }

            // Get the stream
            $stream = AgoraChannel::findOrFail($args['streamId']);

            // Only stream owner can add moderators
            if ($user->id !== $stream->user_id) {
                Log::warning('Unauthorized access to addModerator', [
                    'streamId' => $args['streamId'],
                    'user_id' => $user->id,
                    'stream_owner_id' => $stream->user_id
                ]);
                throw new Exception('Only the stream owner can add moderators');
            }

            // Find or create viewer record
            $viewer = AgoraChannelViewer::firstOrCreate(
                [
                    'agora_channel_id' => $args['streamId'],
                    'user_id' => $args['userId']
                ],
                [
                    'status' => 'active',
                    'is_moderator' => false,
                    'is_blocked' => false,
                    'last_activity_at' => now(),
                    'joined_at' => now()
                ]
            );

            // Update moderator status
            $viewer->is_moderator = true;
            $viewer->save();

            Log::info('Moderator added successfully', [
                'streamId' => $args['streamId'],
                'userId' => $args['userId']
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error in addModerator: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            throw $e;
        }
    }

    public function removeModerator($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        try {
            $user = $context->user();
            Log::info('removeModerator called', [
                'streamId' => $args['streamId'],
                'userId' => $args['userId'],
                'user_id' => $user->id
            ]);

            $validator = Validator::make($args, [
                'streamId' => 'required|exists:agora_channels,id',
                'userId' => 'required|exists:users,id',
            ]);

            if ($validator->fails()) {
                Log::warning('removeModerator validation failed', [
                    'errors' => $validator->errors()->toArray()
                ]);
                throw ValidationException::withMessages($validator->errors()->toArray());
            }

            // Get the stream
            $stream = AgoraChannel::findOrFail($args['streamId']);

            // Only stream owner can remove moderators
            if ($user->id !== $stream->user_id) {
                Log::warning('Unauthorized access to removeModerator', [
                    'streamId' => $args['streamId'],
                    'user_id' => $user->id,
                    'stream_owner_id' => $stream->user_id
                ]);
                throw new Exception('Only the stream owner can remove moderators');
            }

            // Find viewer record
            $viewer = AgoraChannelViewer::where('agora_channel_id', $args['streamId'])
                ->where('user_id', $args['userId'])
                ->first();

            if (!$viewer) {
                // If there is no viewer record, they are implicitly not a moderator
                return true;
            }

            // Update moderator status
            $viewer->is_moderator = false;
            $viewer->save();

            Log::info('Moderator removed successfully', [
                'streamId' => $args['streamId'],
                'userId' => $args['userId']
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error in removeModerator: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Start recording a live stream
     *
     * @param  null  $rootValue
     * @param  array  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @param  \GraphQL\Type\Definition\ResolveInfo  $resolveInfo
     * @return array
     */
    public function startRecording($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        try {
            $user = $context->user();
            Log::info('startRecording called', [
                'channelName' => $args['channelName'],
                'uid' => $args['uid'],
                'user_id' => $user->id
            ]);

            $validator = Validator::make($args, [
                'channelName' => 'required|string',
                'uid' => 'required|string',
            ]);

            if ($validator->fails()) {
                Log::warning('startRecording validation failed', [
                    'errors' => $validator->errors()->toArray()
                ]);
                throw ValidationException::withMessages($validator->errors()->toArray());
            }

            // Find the channel by name
            $channel = AgoraChannel::where('channel_name', $args['channelName'])
                ->first();

            if (!$channel) {
                Log::warning('Channel not found', [
                    'channelName' => $args['channelName']
                ]);
                throw new Exception('Channel not found');
            }

            // Check if the user is the stream owner
            if ($user->id !== $channel->user_id) {
                Log::warning('Unauthorized access to startRecording', [
                    'channelName' => $args['channelName'],
                    'user_id' => $user->id,
                    'channel_owner_id' => $channel->user_id
                ]);
                throw new Exception('Only the stream owner can start recording');
            }

            // Call Agora Cloud Recording API to start recording
            $result = $this->agoraService->startCloudRecording($args['channelName'], $args['uid']);

            // Log the result
            Log::info('Cloud recording started', [
                'channelName' => $args['channelName'],
                'result' => $result
            ]);

            // Update the channel with recording info
            $channel->is_recording = true;
            $channel->recording_resource_id = $result['resourceId'] ?? null;
            $channel->recording_sid = $result['sid'] ?? null;
            $channel->save();

            return $result;
        } catch (\Exception $e) {
            Log::error('Error in startRecording: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * End recording a live stream
     *
     * @param  null  $rootValue
     * @param  array  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @param  \GraphQL\Type\Definition\ResolveInfo  $resolveInfo
     * @return array
     */
    public function endRecording($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        try {
            $user = $context->user();
            Log::info('endRecording called', [
                'channelName' => $args['channelName'],
                'resourceId' => $args['resourceId'],
                'sid' => $args['sid'],
                'user_id' => $user->id
            ]);

            $validator = Validator::make($args, [
                'channelName' => 'required|string',
                'resourceId' => 'required|string',
                'sid' => 'required|string',
            ]);

            if ($validator->fails()) {
                Log::warning('endRecording validation failed', [
                    'errors' => $validator->errors()->toArray()
                ]);
                throw ValidationException::withMessages($validator->errors()->toArray());
            }

            // Find the channel by name
            $channel = AgoraChannel::where('channel_name', $args['channelName'])
                ->first();

            if (!$channel) {
                Log::warning('Channel not found', [
                    'channelName' => $args['channelName']
                ]);
                throw new Exception('Channel not found');
            }

            // Check if the user is the stream owner
            if ($user->id !== $channel->user_id) {
                Log::warning('Unauthorized access to endRecording', [
                    'channelName' => $args['channelName'],
                    'user_id' => $user->id,
                    'channel_owner_id' => $channel->user_id
                ]);
                throw new Exception('Only the stream owner can end recording');
            }

            // Call Agora Cloud Recording API to stop recording
            $result = $this->agoraService->stopCloudRecording(
                $args['channelName'],
                $args['resourceId'],
                $args['sid']
            );

            // Log the result
            Log::info('Cloud recording ended', [
                'channelName' => $args['channelName'],
                'result' => $result
            ]);

            // Update the channel with recording info
            $channel->is_recording = false;

            // Store recording URLs if available
            if (isset($result['serverResponse']['fileList'])) {
                $fileList = $result['serverResponse']['fileList'];
                $recordingUrls = [];

                foreach ($fileList as $file) {
                    if (isset($file['url'])) {
                        $recordingUrls[] = $file['url'];
                    }
                }

                $channel->recording_urls = $recordingUrls;
                $channel->has_recording = !empty($recordingUrls);
            }

            $channel->save();

            return $result;
        } catch (\Exception $e) {
            Log::error('Error in endRecording: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Get viewers for a specific stream
     *
     * @param  null  $rootValue
     * @param  array  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @param  \GraphQL\Type\Definition\ResolveInfo  $resolveInfo
     * @return array
     */
    public function streamViewers($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        try {
            $channelId = $args['channel_id'] ?? null;

            if (!$channelId) {
                return [];
            }

            $viewers = AgoraChannelViewer::where('agora_channel_id', $channelId)
                ->where('status', 'active')
                ->with('user')
                ->orderBy('last_activity_at', 'desc')
                ->limit($args['limit'] ?? 50)
                ->get();

            return $viewers;
        } catch (\Exception $e) {
            Log::error('Error in streamViewers: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return [];
        }
    }

    /**
     * Get a single live stream by ID
     * This method handles the case where multiple records might exist with the same ID
     * and returns only the first (most recent) one
     */
    public function getLiveStream($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        try {
            $user = $context->user();
            $streamId = $args['id'];

            Log::info('getLiveStream called', [
                'user_id' => $user->id,
                'stream_id' => $streamId
            ]);

            // Query for the stream by ID, ordering by created_at desc to get the most recent one
            // This handles the case where there might be multiple records with the same ID
            $stream = AgoraChannel::where('id', $streamId)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$stream) {
                Log::warning('Stream not found', ['stream_id' => $streamId]);
                return null;
            }

            Log::info('Stream found successfully', [
                'stream_id' => $streamId,
                'stream_title' => $stream->title,
                'stream_status' => $stream->status_id
            ]);

            return $stream;
        } catch (\Exception $e) {
            Log::error('Error in getLiveStream: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            throw $e;
        }
    }
}
