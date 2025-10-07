<?php

namespace App\Services;

use App\Models\Video;
use App\Models\VideoMetrics;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use App\Facades\VideoEvent;

/**
 * Video işlemlerini toplu ve optimize şekilde gerçekleştirmek için servis
 * Bu sınıf, büyük ölçekli ve yüksek hacimli video işlemleri için performans optimizasyonları içerir
 */
class VideoBatchOptimizer
{
    /**
     * Redis bağlantısı mevcut mu
     * 
     * @var bool
     */
    protected $redisAvailable = false;
    
    /**
     * Konstrüktör
     */
    public function __construct()
    {
        try {
            // Redis bağlantısı testi
            Redis::ping();
            $this->redisAvailable = true;
        } catch (\Exception $e) {
            $this->redisAvailable = false;
            Log::warning('Redis connection failed in VideoBatchOptimizer', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Video trending skorlarını toplu şekilde optimize ederek günceller
     * 
     * @param array $videoIds Video ID'leri
     * @param bool $useRedisScoring Redis'i kullanarak skoru hesapla
     * @return int Güncellenen video sayısı
     */
    public function optimizedBatchUpdateTrendingScores(array $videoIds, bool $useRedisScoring = true): int
    {
        $count = 0;
        $startTime = microtime(true);
        $traceId = uniqid('batch_trending_', true);
        
        try {
            // Verinin çok büyük olma ihtimaline karşı chunk'lara böl
            $chunks = array_chunk($videoIds, 100);
            
            foreach ($chunks as $chunk) {
                // Redis varsa ve isteniyorsa, Redis ile daha hızlı hesaplama yap
                if ($this->redisAvailable && $useRedisScoring) {
                    $count += $this->redisOptimizedScoring($chunk);
                } else {
                    // Standart DB toplu güncelleme
                    $count += $this->dbOptimizedScoring($chunk);
                }
                
                // Büyük işlemler sırasında GC'yi zorla
                if (count($videoIds) > 1000) {
                    gc_collect_cycles();
                }
            }
            
            // İstatistikleri logla
            $duration = round(microtime(true) - $startTime, 3);
            $videosPerSecond = $count > 0 ? round($count / $duration, 2) : 0;
            
            Log::info('Batch trending score update completed', [
                'video_count' => $count,
                'duration_seconds' => $duration,
                'videos_per_second' => $videosPerSecond,
                'trace_id' => $traceId
            ]);
            
            return $count;
        } catch (\Exception $e) {
            Log::error('Error in batch trending score update', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'video_count' => count($videoIds),
                'trace_id' => $traceId
            ]);
            
            return 0;
        }
    }
    
    /**
     * Redis kullanarak trending skoru hesapla (en yüksek performans için)
     * 
     * @param array $videoIds Video ID'leri
     * @return int Güncellenen video sayısı
     */
    protected function redisOptimizedScoring(array $videoIds): int
    {
        $updatedCount = 0;
        
        // Video verisini tek seferde çek
        $videos = Video::whereIn('_id', $videoIds)
            ->select(['_id', 'likes_count', 'comments_count', 'views_count', 'created_at'])
            ->get();
        
        // Redis pipeline kullanarak işlemi hızlandır
        $pipe = Redis::pipeline();
        
        foreach ($videos as $video) {
            $recencyFactor = $this->calculateRecencyFactor($video->created_at);
            
            // Engagement skoru hesapla
            $engagementScore = ($video->likes_count * 1.5) + 
                              ($video->comments_count * 2.0) + 
                              ($video->views_count * 0.5);
            
            // Trending skoru
            $trendingScore = $engagementScore * $recencyFactor;
            
            // Geçici Redis key oluştur, daha sonra toplu olarak güncellemek için
            $tempKey = "temp_trending:{$video->_id}";
            $pipe->set($tempKey, json_encode([
                'trending_score' => $trendingScore,
                'engagement_score' => $engagementScore
            ]), 'EX', 3600); // 1 saat geçerli
            
            $updatedCount++;
        }
        
        // Pipeline'ı çalıştır
        $pipe->execute();
        
        // Şimdi DB'yi toplu şekilde güncelle
        if ($updatedCount > 0) {
            foreach ($videos as $video) {
                $tempKey = "temp_trending:{$video->_id}";
                $data = json_decode(Redis::get($tempKey), true);
                
                if ($data) {
                    $video->trending_score = $data['trending_score'];
                    $video->engagement_score = $data['engagement_score'];
                    $video->save();
                    
                    // Cache ve key temizle
                    Redis::del($tempKey);
                    Cache::forget("video:{$video->_id}");
                    
                    // VideoMetrics güncellemesi
                    VideoMetrics::updateFromVideo($video);
                }
            }
        }
        
        return $updatedCount;
    }
    
    /**
     * Veritabanı kullanarak trending skoru hesapla
     * 
     * @param array $videoIds Video ID'leri
     * @return int Güncellenen video sayısı
     */
    protected function dbOptimizedScoring(array $videoIds): int
    {
        $updatedCount = 0;
        
        // Video verisini tek seferde çek (eager loading kullanmayarak memory kullanımını azalt)
        $videos = Video::whereIn('_id', $videoIds)
            ->select(['_id', 'likes_count', 'comments_count', 'views_count', 'created_at'])
            ->cursor(); // Büyük veri setleri için cursor kullan
        
        foreach ($videos as $video) {
            $recencyFactor = $this->calculateRecencyFactor($video->created_at);
            
            // Engagement skoru hesapla
            $engagementScore = ($video->likes_count * 1.5) + 
                              ($video->comments_count * 2.0) + 
                              ($video->views_count * 0.5);
            
            // Trending skoru
            $trendingScore = $engagementScore * $recencyFactor;
            
            // Sadece gerekli alanları güncelle, tüm modeli değil
            $video->trending_score = $trendingScore;
            $video->engagement_score = $engagementScore;
            $video->save();
            
            // Cache temizle
            Cache::forget("video:{$video->_id}");
            
            // VideoMetrics güncellemesi (eğer gerekiyorsa)
            VideoMetrics::updateFromVideo($video);
            
            $updatedCount++;
            
            // Belirli aralıklarla memory temizliği
            if ($updatedCount % 100 === 0) {
                gc_collect_cycles();
            }
        }
        
        return $updatedCount;
    }
    
    /**
     * Video oluşturulma tarihine göre güncellik faktörünü hesapla
     * 
     * @param \DateTime|string $createdAt Video oluşturulma tarihi
     * @return float Güncellik faktörü (0-1 arasında)
     */
    protected function calculateRecencyFactor($createdAt): float
    {
        if (is_string($createdAt)) {
            $createdAt = new \DateTime($createdAt);
        }
        
        $now = new \DateTime();
        $daysDiff = $now->diff($createdAt)->days;
        
        // Logaritmik azalan bir değer kullanarak, güncelliği hesapla (daha soft decay)
        // Bu, eskiden beri popüler videoların ani düşüş yaşamamasını sağlar
        if ($daysDiff <= 1) {
            return 1.0; // Son 24 saat
        } elseif ($daysDiff <= 7) {
            return 0.8; // Son hafta
        } elseif ($daysDiff <= 30) {
            return 0.6; // Son ay
        } elseif ($daysDiff <= 90) {
            return 0.4; // Son 3 ay
        } elseif ($daysDiff <= 180) {
            return 0.25; // Son 6 ay
        } else {
            return 0.1; // 6 aydan eski
        }
    }
    
    /**
     * Bellek kullanımını optimize etmek için önbellek TTL değerlerini trafik desenlerine göre dinamik olarak ayarlar
     * 
     * @param bool $highTrafficMode Yüksek trafik modu aktif mi?
     * @return array Güncel TTL değerleri
     */
    public function optimizeCacheTTLs(bool $highTrafficMode = false): array
    {
        // Temel TTL değerleri
        $ttls = [
            'feed_cache_minutes' => 15,
            'profile_cache_minutes' => 20,
            'video_cache_minutes' => 30,
            'user_pref_cache_hours' => 24,
            'search_cache_minutes' => 10
        ];
        
        // Yüksek trafik modunda değerleri artır
        if ($highTrafficMode) {
            $ttls['feed_cache_minutes'] = 30;
            $ttls['profile_cache_minutes'] = 40;
            $ttls['video_cache_minutes'] = 60;
            $ttls['search_cache_minutes'] = 20;
        }
        
        // Geçerli saati kontrol et - akşam saatlerinde cache sürelerini uzat (yoğun kullanım)
        $hour = (int)(new \DateTime())->format('H');
        if ($hour >= 18 && $hour <= 23) { // Akşam 6-11 arası
            $ttls['feed_cache_minutes'] *= 1.5;
            $ttls['profile_cache_minutes'] *= 1.5;
        }
        
        // Eğer Redis ise, daha uzun TTL'ler kullan (Redis daha hızlı)
        if ($this->redisAvailable) {
            array_walk($ttls, function(&$val) {
                $val = (int)($val * 1.5);
            });
        }
        
        // Yapılandırma değerlerini uygula
        config(['app.feed_cache_ttl' => $ttls['feed_cache_minutes']]);
        config(['app.profile_cache_ttl' => $ttls['profile_cache_minutes']]);
        config(['app.video_cache_ttl' => $ttls['video_cache_minutes']]);
        
        return $ttls;
    }
    
    /**
     * Alarm durumunu kontrol et ve gerekirse uyarı gönder
     * 
     * @param array $errorMetrics Hata metrikleri
     * @param int $errorThreshold Hata eşiği
     * @return bool Alarm durumu
     */
    public function checkAlarmCondition(array $errorMetrics, int $errorThreshold = 100): bool
    {
        $totalErrors = array_sum(array_column($errorMetrics, 'count'));
        
        // Eşiği aştıysa alarm durumu
        if ($totalErrors > $errorThreshold) {
            Log::alert('Performance alarm triggered', [
                'total_errors' => $totalErrors,
                'threshold' => $errorThreshold,
                'metrics' => $errorMetrics
            ]);
            
            // Burada e-posta, Slack bildirimi vb. gönderilebilir
            // \Mail::to(config('app.admin_email'))->send(new \App\Mail\PerformanceAlarm($errorMetrics));
            
            return true;
        }
        
        return false;
    }
}
