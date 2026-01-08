<?php

namespace App\Services\Video;

use Exception;
use App\Models\User;
use App\Models\Block;
use App\Models\Video;
use App\Models\Follow;
use App\Services\AdService;
use App\Services\VideoService;
use Illuminate\Support\Facades\Log;
use App\Jobs\Feed\UpdateUserFeedJob;
use App\Services\VideoScoringService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use App\Services\Traits\VideoFeedHelperTrait;

class FeedService
{
    use VideoFeedHelperTrait;

    /*
        types => mixed, following, sport
        cache => feed-videos:{$type}:{$userId}                  --> her kullanıcının önceden hazırlanmış feed video listesi
        redis => user:{$userId}:watched_videos                  --> izlenen videolar
    */

    public function __construct(
        protected VideoScoringService $scoringService,
        protected VideoService $videoService,
        protected AdService $adService
    ) {
    }

    public function getFeed(User $user, string $type = 'mixed', int $limit = 50): array
    {
        return [
            'videos' => $this->getFeedVideos($user, $type, $limit),
            'ads' => $this->adService->getAds($user, ['video_count' => $limit, 'feed_type' => $type]),
        ];
    }

    public function getFeedVideos(User $user, string $type = 'mixed', int $limit = 50)
    {
        $cacheKey = "feed-videos:user:{$user->id}:{$type}";
        $jobInProgressKey = "feed-job-in-progress:user:{$user->id}:{$type}";

        // Öncelikle cache'de kayıtlı bir feed var mı kontrol et
        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);

            // Eğer boş veya geçersiz bir cache varsa, onu yok say ve yeniden oluştur
            if (!$cached || $cached->count() <= 0) {
                Log::warning('Empty or invalid cache found, recreating', [
                    'user_id' => $user->id,
                    'feed_type' => $type
                ]);
                Cache::forget($cacheKey);
            } else {
                // Geçerli bir cache var - aynı videoları takip eden isteklerde göstermemek için ID'leri al
                $videoIds = $cached->pluck('id')->toArray();

                // Job daha önce başlatılmadıysa başlat - arka planda yeni feed hazırla
                if (!Cache::has($jobInProgressKey)) {
                    // Job'un başlatıldığını belirt ve kısa süre cache'le
                    Cache::put($jobInProgressKey, true, now()->addSeconds(60));

                    // Background job başlat ama cache'i hemen silme
                    UpdateUserFeedJob::dispatch($user->id, $type, $limit, $videoIds)
                        ->onQueue('low')
                        ->delay(now()->addSeconds(10));

                    Log::info('Started background job to update feed cache', [
                        'user_id' => $user->id,
                        'feed_type' => $type,
                        'videos_count' => $cached->count()
                    ]);
                }

                // Videolar geçerli ise kullan
                if ($cached->count() > 0) {
                    Log::info('Getting feed videos from cache', [
                        'user_id' => $user->id,
                        'feed_type' => $type,
                        'videos_count' => $cached->count()
                    ]);
                    return $cached;
                }
            }
        }

        // Cache yok veya geçersiz/boş - DB'den getir ve yeni cache oluştur
        $videos = $this->getFeedVideosFromDb($user, ['limit' => $limit], $type);

        // Video listesini cache'le - kısa sürede yeni talep gelirse bunu kullan
        if ($videos->count() > 0) {
            Cache::put($cacheKey, $videos, now()->addMinutes(16));

            // Eğer video varsa ve cache oluştuysa, job'un başlatıldığını belirt
            Cache::put($jobInProgressKey, true, now()->addSeconds(60));

            // 10 saniye sonra yeni bir feed oluşturmak için job planla
            UpdateUserFeedJob::dispatch($user->id, $type, $limit, $videos->pluck('id')->toArray())
                ->onQueue('low')
                ->delay(now()->addSeconds(20)); // Bir sonraki feed için biraz daha fazla zaman bırak

            Log::info('Created new feed from database and scheduled refresh job', [
                'user_id' => $user->id,
                'feed_type' => $type,
                'videos_count' => $videos->count()
            ]);
        } else {
            Log::warning('Failed to generate feed videos from database', [
                'user_id' => $user->id,
                'feed_type' => $type
            ]);
        }

        return $videos;
    }

    /* Feed için dbden videoları getir. */
    public function getFeedVideosFromDb(User $user, array $options = [], string $type = 'mixed', array $ignoreVideoIds = [])
    {
        $limit = $options['limit'] ?? 50;
        $randomFactor = $options['random_factor'] ?? (rand(1, 10) / 100); // 0.01 - 0.10 random factor

        // Engellenen kullanıcıların videolarını dışarıda bırak
        $blockedUserIds = Block::query()
            ->where('blocker_id', $user->id)
            ->pluck('blocked_id')
            ->toArray();

        // İzlenen videoların ID'lerini al
        $watchedVideoIds = $this->getWatchedVideoIds($user->id);

        // İgnore edilecek ID listesini limitleyelim (çok büyük liste performans sorunu yaratabilir)
        if (count($ignoreVideoIds) > 100) {
            $ignoreVideoIds = array_slice($ignoreVideoIds, 0, 100);
            Log::info('Limiting ignoreVideoIds to 100 items', [
                'user_id' => $user->id,
                'original_count' => count($ignoreVideoIds)
            ]);
        }

        // Following kullanıcılarını al (eğer following feed ise)
        $followingUserIds = [];
        if ($type === 'following') {
            $followingUserIds = Follow::query()
                ->where('follower_id', $user->id)
                ->where('status', 'approved')
                ->pluck('followed_id')
                ->toArray();

            // Eğer following feed ise ve kullanıcı kimseyi takip etmiyorsa, boş bir koleksiyon döndür
            if (empty($followingUserIds) && $type === 'following') {
                Log::info('User is not following anyone, returning empty collection', [
                    'user_id' => $user->id,
                    'feed_type' => $type
                ]);
                return collect([]);
            }
        }

        // 1. İzlenmemiş videoları al
        $unwatchedQuery = Video::query()
            ->where('is_private', false)
            ->where('status', 'finished')
            ->whereNotIn('user_id', $blockedUserIds)
            ->whereNotIn('id', $watchedVideoIds)
            ->when(count($ignoreVideoIds) > 0, function ($query) use ($ignoreVideoIds) {
                $query->whereNotIn('id', $ignoreVideoIds);
            })
            ->when($type === 'following', function ($query) use ($followingUserIds) {
                $query->whereIn('user_id', $followingUserIds)->where('is_sport', false);
            })
            ->when($type === 'sport', function ($query) {
                $query->where('is_sport', true);
            })
            ->when($type === 'mixed', function ($query) {
                $query->where('is_sport', false);
            });

        // Trending score'a göre sırala
        $this->scoringService->applyTrendingScoreSort($unwatchedQuery);

        // Random factor'a göre sırala
        if (!empty($randomFactor) && $randomFactor > 0) {
            try {
                $this->videoService->applyRandomizedSorting($unwatchedQuery, $randomFactor, $user->id);
            } catch (\Exception $e) {
                Log::warning('Failed to apply randomized sorting to unwatched videos', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id
                ]);
                // Hata durumunda basit sıralama uygula
                $unwatchedQuery->orderBy('trending_score', 'desc')->orderBy('created_at', 'desc');
            }
        }

        // İzlenmemiş videoları al - daha fazla video çekelim ki shuffle sonrası yeterli sayıda olsun
        $unwatchedVideos = $unwatchedQuery->with('user')->limit($limit * 2)->get();
        $unwatchedCount = $unwatchedVideos->count();

        // Eğer izlenmemiş videolar limiti doldurmuyorsa, izlenmiş videolardan ekle
        $videos = $unwatchedVideos;
        if ($unwatchedCount < $limit) {
            $remaining = $limit - $unwatchedCount;

            // İzlenmiş videolardan rastgele seç
            $watchedQuery = Video::query()
                ->where('is_private', false)
                ->where('status', 'finished')
                ->whereNotIn('user_id', $blockedUserIds)
                ->whereIn('id', $watchedVideoIds) // Sadece izlenmiş videolardan seç
                ->when(count($ignoreVideoIds) > 0, function ($query) use ($ignoreVideoIds) {
                    $query->whereNotIn('id', $ignoreVideoIds);
                })
                ->when($type === 'following', function ($query) use ($followingUserIds) {
                    $query->whereIn('user_id', $followingUserIds)->where('is_sport', false);
                })
                ->when($type === 'sport', function ($query) {
                    $query->where('is_sport', true);
                })
                ->when($type === 'mixed', function ($query) {
                    $query->where('is_sport', false);
                });

            // İzlenmiş videolardan seçim yaparken, trending score ve random factor kullan
            $this->scoringService->applyTrendingScoreSort($watchedQuery);
            if (!empty($randomFactor) && $randomFactor > 0) {
                try {
                    $this->videoService->applyRandomizedSorting($watchedQuery, $randomFactor * 2, $user->id); // İzlenmiş videolarda daha fazla randomize
                } catch (\Exception $e) {
                    Log::warning('Failed to apply randomized sorting to watched videos', [
                        'error' => $e->getMessage(),
                        'user_id' => $user->id
                    ]);
                    // Hata durumunda basit sıralama uygula
                    $watchedQuery->orderBy('trending_score', 'desc')->orderBy('created_at', 'desc');
                }
            }

            // İzlenmiş videolardan daha fazla çek (en az 3 katı) ve shuffle yaparak randomize et
            $watchedVideos = $watchedQuery->with('user')->limit($remaining * 3)->get();

            if ($watchedVideos->count() > 0) {
                $watchedVideos = $watchedVideos->shuffle()->take($remaining);
                // İzlenmemiş ve izlenmiş videoları birleştir
                $videos = $unwatchedVideos->concat($watchedVideos);
            }
        }

        // Eğer hala yeterli video yoksa, tüm videoları al (izlenmiş/izlenmemiş filtresi olmadan)
        // Burada explicit olarak izlenmiş videoları dışarıda bırakmayarak, izlenmiş videolardan sonra yeni videolar da getirilmesini sağlıyoruz
        if ($videos->count() < $limit) {
            $stillNeeded = $limit - $videos->count();

            // Şu ana kadar eklenen video ID'lerini al - bunları tekrar sorgulamayalım
            $alreadyIncludedIds = $videos->pluck('id')->toArray();

            // Önce standart sorgulama - izlenmiş izlenmemiş farketmeksizin video ara
            $allVideosQuery = Video::query()
                ->where('is_private', false)
                ->where('status', 'finished')
                ->whereNotIn('user_id', $blockedUserIds)
                ->when(count($alreadyIncludedIds) > 0, function ($query) use ($alreadyIncludedIds) {
                    $query->whereNotIn('id', $alreadyIncludedIds);
                })
                ->when($type === 'following', function ($query) use ($followingUserIds) {
                    $query->whereIn('user_id', $followingUserIds)->where('is_sport', false);
                })
                ->when($type === 'sport', function ($query) {
                    $query->where('is_sport', true);
                })
                ->when($type === 'mixed', function ($query) {
                    $query->where('is_sport', false);
                });

            // Çok fazla video izlenmiş olmadığı durumlarda ilk olarak ignoreVideoIds'i dikkate al
            // ama hiç video bulunamazsa bu filtreyi kaldır
            if (count($ignoreVideoIds) > 0) {
                $allVideosQuery->whereNotIn('id', $ignoreVideoIds);
            }

            $this->scoringService->applyTrendingScoreSort($allVideosQuery);

            // Daha fazla video çekelim ve karıştıralım
            $additionalVideos = $allVideosQuery->with('user')->limit($stillNeeded * 3)->get();

            if ($additionalVideos->count() > 0) {
                $additionalVideos = $additionalVideos->shuffle()->take($stillNeeded);
                $videos = $videos->concat($additionalVideos);

                Log::info('Added additional videos to meet the limit', [
                    'user_id' => $user->id,
                    'additional_count' => $additionalVideos->count(),
                    'total_count' => $videos->count()
                ]);
            } else if (count($ignoreVideoIds) > 0) {
                // Eğer ilk denemede hiç sonuç bulamazsak ve ignoreVideoIds varsa, bu filtreyi kaldırıp tekrar deneyelim
                Log::info('No results with ignoreVideoIds filter, trying without it', [
                    'user_id' => $user->id,
                    'ignore_ids_count' => count($ignoreVideoIds)
                ]);

                // ignoreVideoIds filtresini kaldırarak tekrar dene
                $allVideosQuery = Video::query()
                    ->where('is_private', false)
                    ->where('status', 'finished')
                    ->whereNotIn('user_id', $blockedUserIds)
                    ->when(count($alreadyIncludedIds) > 0, function ($query) use ($alreadyIncludedIds) {
                        $query->whereNotIn('id', $alreadyIncludedIds);
                    })
                    ->when($type === 'following', function ($query) use ($followingUserIds) {
                        $query->whereIn('user_id', $followingUserIds)->where('is_sport', false);
                    })
                    ->when($type === 'sport', function ($query) {
                        $query->where('is_sport', true);
                    })
                    ->when($type === 'mixed', function ($query) {
                        $query->where('is_sport', false);
                    });

                $this->scoringService->applyTrendingScoreSort($allVideosQuery);
                $additionalVideos = $allVideosQuery->with('user')->limit($stillNeeded * 3)->get();

                if ($additionalVideos->count() > 0) {
                    $additionalVideos = $additionalVideos->shuffle()->take($stillNeeded);
                    $videos = $videos->concat($additionalVideos);

                    Log::info('Added videos after removing ignoreVideoIds filter', [
                        'user_id' => $user->id,
                        'additional_count' => $additionalVideos->count(),
                        'total_count' => $videos->count()
                    ]);
                } else {
                    // Son çare - alreadyIncludedIds filtresini de kaldır, video bulmak için her şeyi dene
                    // Bu sadece gerçekten hiç video kalmadığında çalışacak bir son çare
                    $finalAttemptQuery = Video::query()
                        ->where('is_private', false)
                        ->where('status', 'finished')
                        ->whereNotIn('user_id', $blockedUserIds)
                        ->when($type === 'following', function ($query) use ($followingUserIds) {
                            $query->whereIn('user_id', $followingUserIds)->where('is_sport', false);
                        })
                        ->when($type === 'sport', function ($query) {
                            $query->where('is_sport', true);
                        })
                        ->when($type === 'mixed', function ($query) {
                            $query->where('is_sport', false);
                        });

                    $this->scoringService->applyTrendingScoreSort($finalAttemptQuery);
                    $finalVideos = $finalAttemptQuery->with('user')->limit($stillNeeded * 4)->get(); // Daha fazla video al

                    if ($finalVideos->count() > 0) {
                        $finalVideos = $finalVideos->shuffle()->take($stillNeeded);
                        $videos = $videos->concat($finalVideos);

                        Log::info('Added final attempt videos with minimal filtering', [
                            'user_id' => $user->id,
                            'final_count' => $finalVideos->count(),
                            'total_count' => $videos->count()
                        ]);
                    } else {
                        // Gerçekten hiçbir video kalmadı - bu durumda izlenen videoları tekrar göster
                        Log::warning('No videos found with any filter, reusing watched videos', [
                            'user_id' => $user->id,
                            'feed_type' => $type
                        ]);

                        // Tüm izlenmiş videoları tekrar kullanılabilir yap
                        $finalVideosQuery = Video::query()
                            ->where('is_private', false)
                            ->where('status', 'finished')
                            ->whereNotIn('user_id', $blockedUserIds)
                            ->when($type === 'following', function ($query) use ($followingUserIds) {
                                $query->whereIn('user_id', $followingUserIds)->where('is_sport', false);
                            })
                            ->when($type === 'sport', function ($query) {
                                $query->where('is_sport', true);
                            })
                            ->when($type === 'mixed', function ($query) {
                                $query->where('is_sport', false);
                            });

                        $this->scoringService->applyTrendingScoreSort($finalVideosQuery);
                        $recycledVideos = $finalVideosQuery->with('user')->limit(100)->get(); // Çok daha fazla video al

                        if ($recycledVideos->count() > 0) {
                            $recycledVideos = $recycledVideos->shuffle()->take($stillNeeded);
                            $videos = $videos->concat($recycledVideos);

                            Log::info('Added recycled videos as last resort', [
                                'user_id' => $user->id,
                                'recycled_count' => $recycledVideos->count(),
                                'total_count' => $videos->count()
                            ]);
                        }
                    }
                }
            }
        }

        // Seller videolarını (yüksek trending_score) ayrı tut ve en üste taşı
        $sellerVideos = $videos->filter(fn($v) => ($v->trending_score ?? 0) >= 1000000);
        $regularVideos = $videos->filter(fn($v) => ($v->trending_score ?? 0) < 1000000);

        // Sadece normal videoları karıştır (seller videolarına dokunma)
        if ($regularVideos->count() > 2) {
            $limitOfShuffle = intval(floor($regularVideos->count() / 3));
            if ($limitOfShuffle > 0) {
                $firstChunk = $regularVideos->take($limitOfShuffle)->shuffle();
                $rest = $regularVideos->slice($limitOfShuffle);
                $regularVideos = $firstChunk->concat($rest);
            } else {
                // Çok az video varsa tümünü karıştır
                $regularVideos = $regularVideos->shuffle();
            }
        }

        // Seller videolarını en başa ekle, sonra normal videolar
        $videos = $sellerVideos->concat($regularVideos);

        Log::info('Feed videos priority applied', [
            'user_id' => $user->id,
            'feed_type' => $type,
            'seller_videos_count' => $sellerVideos->count(),
            'regular_videos_count' => $regularVideos->count()
        ]);

        // Limit kadar video döndür
        $videos = $videos->take($limit);

        Log::info('Getting feed videos from DB', [
            'user_id' => $user->id,
            'feed_type' => $type,
            'unwatched_count' => $unwatchedCount,
            'total_videos_count' => $videos->count(),
            'limit' => $limit
        ]);

        return $videos;
    }

    public function updateFeedCache(User $user, string $type = 'mixed', int $limit = 50, array $ignoreVideoIds = []): void
    {
        $videos = $this->getFeedVideosFromDb($user, ['limit' => $limit], $type, $ignoreVideoIds);

        $cacheKey = "feed-videos:user:{$user->id}:{$type}";

        Cache::put($cacheKey, $videos, now()->addMinutes(16));
    }

    public function markAsWatched(string $userId, string $videoId): void
    {
        $key = "user:{$userId}:watched_videos";
        Redis::sadd($key, $videoId);

        // max 500 video izlenmiş olsun daha fazlası gelirse izlenenlerden 1 tane silinir hep 500de kalır küme şişmesin
        if (Redis::scard($key) > 500) {
            Redis::spop($key);
        }
    }

    public function getWatchedVideoIds(string $userId): array
    {
        return Redis::smembers("user:{$userId}:watched_videos");
    }
}
