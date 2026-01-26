<?php

namespace App\Services\LiveStream;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Services\FollowService;
use App\Models\Agora\AgoraChannel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Models\Agora\AgoraChannelViewer;
use App\Models\Agora\AgoraChannelMessage;
use App\Models\Agora\AgoraStreamStatistic;

class LiveStreamAnalyticsService
{
    public function syncLikesToDb(AgoraChannel $stream): void
    {
        $redisKey = "agora_channel:{$stream->id}:likes";

        $likeCount = Redis::get($redisKey);

        if ($likeCount === null) {
            return;
        }

        $likeCount = (int)$likeCount;
        $currentLikes = (int)$stream->total_likes;

        $stream->update([
            'total_likes' => $currentLikes + $likeCount
        ]);

        Redis::del($redisKey);
    }

    public function saveStreamStatistics(AgoraChannel $stream): ?AgoraStreamStatistic
    {
        try {
            // Yayın tamamlandı mı kontrol et
            if ($stream->status_id != AgoraChannel::STATUS_ENDED) {
                throw new Exception('Stream is not ended yet');
            }

            // Yayın süresi
            $startedAt = $stream->started_at ?: $stream->created_at;
            $endedAt = $stream->ended_at ?: now();
            $duration = (int) $startedAt->diffInSeconds($endedAt); // ✅ FIX: Cast to integer

            // İstatistikleri topla
            $viewers = AgoraChannelViewer::where('agora_channel_id', $stream->id)->get();

            $totalViewers = $viewers->count();
            $uniqueViewers = $viewers->pluck('user_id')->unique()->count();

            // Aktif izleyici süreleri
            $watchTimes = $viewers->map(function ($viewer) {
                return $viewer->watch_duration ?: 0;
            });

            $avgWatchTime = $watchTimes->count() > 0 ? $watchTimes->avg() : 0;

            // Mesaj sayısı
            $totalComments = AgoraChannelMessage::where('agora_channel_id', $stream->id)->count();

            // İstatistik objesi oluştur
            $statistic = new AgoraStreamStatistic();
            $statistic->agora_channel_id = $stream->id;
            $statistic->user_id = $stream->user_id;
            $statistic->date = $startedAt->toDateString();
            $statistic->total_stream_duration = $duration;
            $statistic->total_viewers = $totalViewers;
            $statistic->unique_viewers = $uniqueViewers;
            $statistic->max_concurrent_viewers = $stream->max_viewer_count;
            $statistic->avg_watch_time = (int) $avgWatchTime;
            $statistic->total_comments = $totalComments;
            $statistic->total_likes = $stream->total_likes;
            $statistic->total_gifts = $stream->total_gifts;
            $statistic->total_coins_earned = $stream->total_coins_earned;

            // Yeni takipçi sayısını hesapla
            $statistic->new_followers_gained = $this->calculateNewFollowers($stream);

            $statistic->save();

            return $statistic;
        } catch (Exception $e) {
            Log::error('Failed to save stream statistics', [
                'stream_id' => $stream->id,
                'error' => $e->getMessage()
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * Kullanıcının yayın istatistiklerini getirir
     *
     * @param User $user
     * @param string $period day|week|month|year
     * @return array
     */
    public function getUserStreamStats(User $user, string $period = 'month'): array
    {
        try {
            // Tarih aralığı belirleme
            $endDate = now();
            $startDate = $this->getStartDateByPeriod($endDate, $period);

            // Kullanıcının yayın istatistiklerini al
            $statistics = AgoraStreamStatistic::where('user_id', $user->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->orderBy('date', 'asc')
                ->get();

            if ($statistics->isEmpty()) {
                return $this->getEmptyStats($period);
            }

            // Temel özet metrikleri
            $summary = [
                'total_streams' => $statistics->count(),
                'total_stream_duration' => $statistics->sum('total_stream_duration'),
                'total_viewers' => $statistics->sum('total_viewers'),
                'unique_viewers' => $statistics->sum('unique_viewers'),
                'avg_viewers_per_stream' => $statistics->avg('unique_viewers'),
                'max_concurrent_viewers' => $statistics->max('max_concurrent_viewers'),
                'total_comments' => $statistics->sum('total_comments'),
                'total_likes' => $statistics->sum('total_likes'),
                'total_gifts' => $statistics->sum('total_gifts'),
                'total_coins_earned' => $statistics->sum('total_coins_earned'),
                'new_followers_gained' => $statistics->sum('new_followers_gained')
            ];

            // Trend verileri (tarih bazlı)
            $trendData = $this->prepareTrendData($statistics, $startDate, $endDate, $period);

            return [
                'summary' => $summary,
                'trends' => $trendData,
                'period' => $period,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString()
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get user stream stats', [
                'user_id' => $user->id,
                'period' => $period,
                'error' => $e->getMessage()
            ]);

            return $this->getEmptyStats($period);
        }
    }

    /**
     * Yayın analitiğini getirir
     *
     * @param AgoraChannel $stream
     * @return array
     */
    public function getStreamAnalytics(AgoraChannel $stream): array
    {
        try {
            $statistic = AgoraStreamStatistic::where('agora_channel_id', $stream->id)->first();

            if (!$statistic) {
                // Eğer yayın hala aktifse, geçici istatistikler oluştur
                if ($stream->status_id == AgoraChannel::STATUS_LIVE) {
                    return $this->getActiveStreamStats($stream);
                }

                return $this->getEmptyStreamStats();
            }

            // İzleyici demografisi
            $viewerDemographics = $this->getViewerDemographics($stream);

            return [
                'stream_id' => $stream->id,
                'title' => $stream->title,
                'status' => $stream->status_id,
                'duration' => $statistic->total_stream_duration,
                'started_at' => $stream->started_at?->toDateTimeString(),
                'ended_at' => $stream->ended_at?->toDateTimeString(),

                'viewer_stats' => [
                    'total_viewers' => $statistic->total_viewers,
                    'unique_viewers' => $statistic->unique_viewers,
                    'max_concurrent_viewers' => $statistic->max_concurrent_viewers,
                    'avg_watch_time' => $statistic->avg_watch_time
                ],

                'engagement_stats' => [
                    'total_comments' => $statistic->total_comments,
                    'total_likes' => $statistic->total_likes,
                    'total_gifts' => $statistic->total_gifts,
                    'new_followers' => $statistic->new_followers_gained
                ],

                'revenue_stats' => [
                    'total_coins_earned' => $statistic->total_coins_earned
                ],

                'viewer_demographics' => $viewerDemographics,

                'top_donors' => app(LiveStreamGiftService::class)->getTopDonators($stream->id, 5)
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get stream analytics', [
                'stream_id' => $stream->id,
                'error' => $e->getMessage()
            ]);

            return $this->getEmptyStreamStats();
        }
    }

    /**
     * Kullanıcının performans özet verilerini alır
     *
     * @param User $user
     * @return array
     */
    public function getUserPerformanceSummary(User $user): array
    {
        try {
            // Son 30 gün
            $endDate = now();
            $startDate = $endDate->copy()->subDays(30);

            // Kullanıcının yayın istatistiklerini al
            $statistics = AgoraStreamStatistic::where('user_id', $user->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->get();

            // Toplam yayın sayısı
            $totalStreams = $statistics->count();

            // Önceki 30 günlük dönem
            $previousEndDate = $startDate->copy()->subDay();
            $previousStartDate = $previousEndDate->copy()->subDays(30);

            $previousStatistics = AgoraStreamStatistic::where('user_id', $user->id)
                ->whereBetween('date', [$previousStartDate, $previousEndDate])
                ->get();

            $previousTotalStreams = $previousStatistics->count();

            // Değişim yüzdeleri
            $streamChange = $previousTotalStreams > 0 ?
                (($totalStreams - $previousTotalStreams) / $previousTotalStreams) * 100 : 0;

            $currentCoins = $statistics->sum('total_coins_earned');
            $previousCoins = $previousStatistics->sum('total_coins_earned');

            $coinChange = $previousCoins > 0 ?
                (($currentCoins - $previousCoins) / $previousCoins) * 100 : 0;

            $currentViewers = $statistics->sum('unique_viewers');
            $previousViewers = $previousStatistics->sum('unique_viewers');

            $viewerChange = $previousViewers > 0 ?
                (($currentViewers - $previousViewers) / $previousViewers) * 100 : 0;

            $currentFollowers = $statistics->sum('new_followers_gained');
            $previousFollowers = $previousStatistics->sum('new_followers_gained');

            $followerChange = $previousFollowers > 0 ?
                (($currentFollowers - $previousFollowers) / $previousFollowers) * 100 : 0;

            return [
                'total_streams' => $totalStreams,
                'stream_change_percentage' => round($streamChange, 2),

                'total_coins_earned' => $currentCoins,
                'coin_change_percentage' => round($coinChange, 2),

                'total_viewers' => $currentViewers,
                'viewer_change_percentage' => round($viewerChange, 2),

                'new_followers' => $currentFollowers,
                'follower_change_percentage' => round($followerChange, 2),

                'avg_stream_duration' => $statistics->avg('total_stream_duration'),
                'best_performing_stream' => $this->getBestPerformingStreamId($statistics),

                'period' => '30_days',
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString()
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get user performance summary', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return [
                'total_streams' => 0,
                'stream_change_percentage' => 0,
                'total_coins_earned' => 0,
                'coin_change_percentage' => 0,
                'total_viewers' => 0,
                'viewer_change_percentage' => 0,
                'new_followers' => 0,
                'follower_change_percentage' => 0,
                'avg_stream_duration' => 0,
                'best_performing_stream' => null,
                'period' => '30_days'
            ];
        }
    }

    /**
     * Tüm izleyici kayıtlarını günceller (yayın bittiğinde)
     *
     * @param AgoraChannel $stream
     * @return void
     */
    public function updateViewerRecords(AgoraChannel $stream): void
    {
        // MongoDB'den hala aktif olan izleyicileri al
        $activeViewers = AgoraChannelViewer::query()
            ->where('agora_channel_id', $stream->id)
            ->whereNull('left_at')
            ->get();

        // Her bir izleyici kaydını güncelle
        foreach ($activeViewers as $viewer) {
            $viewer->left_at = $stream->ended_at;

            // İzleme süresini güncelle
            if ($viewer->joined_at) {
                $viewer->watch_duration = (int) $stream->ended_at->diffInSeconds($viewer->joined_at); // ✅ FIX: Cast to integer
            }

            $viewer->save();
        }
    }

    /**
     * Aktif yayının geçici istatistiklerini oluşturur
     *
     * @param AgoraChannel $stream
     * @return array
     */
    protected function getActiveStreamStats(AgoraChannel $stream): array
    {
        // Aktif izleyici sayısı
        $activeViewers = AgoraChannelViewer::where('agora_channel_id', $stream->id)
            ->where('status', AgoraChannelViewer::STATUS_ACTIVE)
            ->count();

        // Toplam izleyici sayısı
        $totalViewers = AgoraChannelViewer::where('agora_channel_id', $stream->id)->count();

        // Benzersiz izleyici sayısı
        $uniqueViewers = AgoraChannelViewer::where('agora_channel_id', $stream->id)
            ->distinct('user_id')
            ->count();

        // Yayın süresi
        $startedAt = $stream->started_at ?: $stream->created_at;
        $duration = $startedAt->diffInSeconds(now());

        // Mesaj sayısı
        $totalComments = DB::connection('mongodb')
            ->collection('agora_channel_messages')
            ->where('agora_channel_id', $stream->id)
            ->count();

        // İzleyici demografisi
        $viewerDemographics = $this->getViewerDemographics($stream);

        return [
            'stream_id' => $stream->id,
            'title' => $stream->title,
            'status' => $stream->status_id,
            'duration' => $duration,
            'started_at' => $startedAt->toDateTimeString(),
            'ended_at' => null,

            'viewer_stats' => [
                'active_viewers' => $activeViewers,
                'total_viewers' => $totalViewers,
                'unique_viewers' => $uniqueViewers,
                'max_concurrent_viewers' => $stream->max_viewer_count
            ],

            'engagement_stats' => [
                'total_comments' => $totalComments,
                'total_likes' => $stream->total_likes,
                'total_gifts' => $stream->total_gifts
            ],

            'revenue_stats' => [
                'total_coins_earned' => $stream->total_coins_earned
            ],

            'viewer_demographics' => $viewerDemographics,

            'top_donors' => app(LiveStreamGiftService::class)->getTopDonators($stream->id, 5)
        ];
    }

    /**
     * Boş yayın istatistikleri döndürür
     *
     * @return array
     */
    protected function getEmptyStreamStats(): array
    {
        return [
            'stream_id' => null,
            'title' => null,
            'status' => null,
            'duration' => 0,
            'started_at' => null,
            'ended_at' => null,

            'viewer_stats' => [
                'total_viewers' => 0,
                'unique_viewers' => 0,
                'max_concurrent_viewers' => 0,
                'avg_watch_time' => 0
            ],

            'engagement_stats' => [
                'total_comments' => 0,
                'total_likes' => 0,
                'total_gifts' => 0,
                'new_followers' => 0
            ],

            'revenue_stats' => [
                'total_coins_earned' => 0
            ],

            'viewer_demographics' => [
                'by_gender' => [],
                'by_age' => [],
                'by_location' => []
            ],

            'top_donors' => []
        ];
    }

    /**
     * Belirtilen döneme göre boş istatistikler döndürür
     *
     * @param string $period
     * @return array
     */
    protected function getEmptyStats(string $period): array
    {
        $endDate = now();
        $startDate = $this->getStartDateByPeriod($endDate, $period);

        return [
            'summary' => [
                'total_streams' => 0,
                'total_stream_duration' => 0,
                'total_viewers' => 0,
                'unique_viewers' => 0,
                'avg_viewers_per_stream' => 0,
                'max_concurrent_viewers' => 0,
                'total_comments' => 0,
                'total_likes' => 0,
                'total_gifts' => 0,
                'total_coins_earned' => 0,
                'new_followers_gained' => 0
            ],
            'trends' => [
                'dates' => [],
                'viewers' => [],
                'comments' => [],
                'coins' => []
            ],
            'period' => $period,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString()
        ];
    }

    /**
     * Döneme göre başlangıç tarihini belirler
     *
     * @param Carbon $endDate
     * @param string $period
     * @return Carbon
     */
    protected function getStartDateByPeriod(Carbon $endDate, string $period): Carbon
    {
        switch ($period) {
            case 'day':
                return $endDate->copy()->subDay();
            case 'week':
                return $endDate->copy()->subWeek();
            case 'month':
                return $endDate->copy()->subMonth();
            case 'year':
                return $endDate->copy()->subYear();
            default:
                return $endDate->copy()->subMonth();
        }
    }

    /**
     * İstatistiklerden en iyi performans gösteren yayını bulur
     *
     * @param Collection $statistics
     * @return string|null
     */
    protected function getBestPerformingStreamId(Collection $statistics): ?string
    {
        if ($statistics->isEmpty()) {
            return null;
        }

        // Coin kazancına göre sırala
        $bestByCoin = $statistics->sortByDesc('total_coins_earned')->first();

        return $bestByCoin ? $bestByCoin->agora_channel_id : null;
    }

    /**
     * İzleyici demografisini getirir
     *
     * @param AgoraChannel $stream
     * @return array
     */
    protected function getViewerDemographics(AgoraChannel $stream): array
    {
        try {
            // Benzersiz izleyicileri al
            $viewers = AgoraChannelViewer::where('agora_channel_id', $stream->id)
                ->get();

            if ($viewers->isEmpty()) {
                return [
                    'by_gender' => [],
                    'by_age' => [],
                    'by_location' => []
                ];
            }

            // Kullanıcı ID'lerini topla
            $userIds = $viewers->pluck('user_id')->unique()->toArray();

            // Kullanıcı verilerini al
            $users = User::whereIn('id', $userIds)->get();

            // Cinsiyet dağılımı
            $genderCounts = [
                'male' => 0,
                'female' => 0,
                'other' => 0
            ];

            foreach ($users as $user) {
                // @todo bu func olayını kontrol et genderlar dbden geliyor artık !!
                $gender = $user->gender ? strtolower($user->gender->name) : 'Diğer';

                if ($gender == 'male') {
                    $genderCounts['male']++;
                } elseif ($gender == 'female') {
                    $genderCounts['female']++;
                } else {
                    $genderCounts['other']++;
                }
            }

            // Yaş dağılımı
            $ageCounts = [
                '13-17' => 0,
                '18-24' => 0,
                '25-34' => 0,
                '35-44' => 0,
                '45+' => 0
            ];

            foreach ($users as $user) {
                if (!$user->birthday) continue;

                $age = Carbon::parse($user->birthday)->age;

                if ($age < 18) {
                    $ageCounts['13-17']++;
                } elseif ($age < 25) {
                    $ageCounts['18-24']++;
                } elseif ($age < 35) {
                    $ageCounts['25-34']++;
                } elseif ($age < 45) {
                    $ageCounts['35-44']++;
                } else {
                    $ageCounts['45+']++;
                }
            }

            // Lokasyon dağılımı (ülkeye göre)
            $locationCounts = [];

            foreach ($users as $user) {
                $country = $user->country ?? 'Unknown';

                if (!isset($locationCounts[$country])) {
                    $locationCounts[$country] = 0;
                }

                $locationCounts[$country]++;
            }

            // En çok görülen 5 ülkeyi al
            arsort($locationCounts);
            $locationCounts = array_slice($locationCounts, 0, 5);

            return [
                'by_gender' => $genderCounts,
                'by_age' => $ageCounts,
                'by_location' => $locationCounts
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get viewer demographics', [
                'stream_id' => $stream->id,
                'error' => $e->getMessage()
            ]);

            return [
                'by_gender' => [],
                'by_age' => [],
                'by_location' => []
            ];
        }
    }

    /**
     * Trend verilerini hazırlar
     *
     * @param Collection $statistics
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string $period
     * @return array
     */
    protected function prepareTrendData(Collection $statistics, Carbon $startDate, Carbon $endDate, string $period): array
    {
        $dateFormat = $this->getDateFormatByPeriod($period);
        $interval = $this->getIntervalByPeriod($period);

        // Tüm tarih noktalarını oluştur
        $datePoints = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $dateKey = $current->format($dateFormat);
            $datePoints[$dateKey] = [
                'viewers' => 0,
                'comments' => 0,
                'coins' => 0
            ];

            $current->add($interval);
        }

        // İstatistikleri dolduralım
        foreach ($statistics as $stat) {
            $dateKey = Carbon::parse($stat->date)->format($dateFormat);

            if (isset($datePoints[$dateKey])) {
                $datePoints[$dateKey]['viewers'] += $stat->unique_viewers;
                $datePoints[$dateKey]['comments'] += $stat->total_comments;
                $datePoints[$dateKey]['coins'] += $stat->total_coins_earned;
            }
        }

        // Trend veri formatını hazırla
        return [
            'dates' => array_keys($datePoints),
            'viewers' => array_column($datePoints, 'viewers'),
            'comments' => array_column($datePoints, 'comments'),
            'coins' => array_column($datePoints, 'coins')
        ];
    }

    /**
     * Döneme göre tarih formatını belirler
     *
     * @param string $period
     * @return string
     */
    protected function getDateFormatByPeriod(string $period): string
    {
        switch ($period) {
            case 'day':
                return 'H:00'; // Saat (örn. 14:00)
            case 'week':
                return 'D'; // Haftanın günü (örn. 1-7)
            case 'month':
                return 'd M'; // Ayın günü (örn. 15 Jan)
            case 'year':
                return 'M Y'; // Ay (örn. Jan 2025)
            default:
                return 'd M'; // Varsayılan: Ayın günü
        }
    }

    /**
     * Döneme göre aralığı belirler
     *
     * @param string $period
     * @return \DateInterval
     */
    protected function getIntervalByPeriod(string $period): \DateInterval
    {
        switch ($period) {
            case 'day':
                return new \DateInterval('PT1H'); // 1 saat
            case 'week':
                return new \DateInterval('P1D'); // 1 gün
            case 'month':
                return new \DateInterval('P1D'); // 1 gün
            case 'year':
                return new \DateInterval('P1M'); // 1 ay
            default:
                return new \DateInterval('P1D'); // Varsayılan: 1 gün
        }
    }

    /**
     * Yeni takipçi sayısını hesaplar
     *
     * @param AgoraChannel $stream
     * @return int
     */
    protected function calculateNewFollowers(AgoraChannel $stream): int
    {
        // Bu mantık takip sisteminize bağlı olarak değişebilir
        // Burada basit bir örnek gösteriyoruz
        try {
            // Yayın sırasında yeni takip eden kullanıcı sayısını bul
            if (!$stream->started_at || !$stream->ended_at) {
                return 0;
            }

            $followService = app(FollowService::class);

            return $followService->getNewFollowersCount(
                $stream->user_id,
                $stream->started_at,
                $stream->ended_at
            );
        } catch (Exception $e) {
            Log::error('Failed to calculate new followers', [
                'stream_id' => $stream->id,
                'error' => $e->getMessage()
            ]);

            return 0;
        }
    }
}
