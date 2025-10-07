<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * MetricsService - Performance ve monitoring metrikleri için merkezi servis
 * 
 * Bu servis, uygulamanın farklı bölümlerindeki performans metriklerini
 * izlemek ve kaydetmek için kullanılır. Redis üzerinde geçici metrikler saklanır
 * ve isteğe bağlı olarak kalıcı depolama için PerformanceMetric modeline kaydedilir.
 */
class MetricsService
{
    /**
     * Sayaç metriğini artır
     *
     * @param string $name Metrik adı
     * @param int $value Artış miktarı
     * @param array $tags İlişkili etiketler
     * @return bool Başarı durumu
     */
    public function incrementCounter(string $name, int $value = 1, array $tags = []): bool
    {
        try {
            // Redis'te gerçek zamanlı izleme için sakla
            $redisKey = "metrics:counter:{$name}";
            Redis::incrby($redisKey, $value);
            
            // Sınırsız büyümeyi önlemek için sona erme süresi ayarla
            Redis::expire($redisKey, 24 * 60 * 60); // 1 gün
            
            // Etiketleri sakla (varsa)
            if (!empty($tags)) {
                $tagKey = "metrics:tags:{$name}";
                Redis::hMSet($tagKey, $tags);
                Redis::expire($tagKey, 24 * 60 * 60);
            }
            
            // İzleme için artışı logla
            Log::debug("Metrik artırıldı: {$name}", [
                'value' => $value,
                'tags' => $tags
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::warning("Metrik artırma başarısız: {$name}", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Zamanlama metriği kaydet
     *
     * @param string $name Metrik adı
     * @param float $valueMs Milisaniye cinsinden değer
     * @param array $tags İlişkili etiketler
     * @return bool Başarı durumu
     */
    public function recordTiming(string $name, float $valueMs, array $tags = []): bool
    {
        try {
            // Redis'te gerçek zamanlı izleme için sakla
            $redisKey = "metrics:timing:{$name}";
            Redis::zadd($redisKey, time(), $valueMs);
            
            // Sınırsız büyümeyi önlemek için son 1000 değeri tut
            Redis::zremrangebyrank($redisKey, 0, -1001);
            
            // Sona erme süresi ayarla
            Redis::expire($redisKey, 7 * 24 * 60 * 60); // 7 gün
            
            // Etiketleri sakla (varsa)
            if (!empty($tags)) {
                $tagKey = "metrics:tags:{$name}";
                Redis::hMSet($tagKey, $tags);
                Redis::expire($tagKey, 7 * 24 * 60 * 60);
            }
            
            // İzleme için logla
            Log::debug("Zamanlama kaydedildi: {$name}", [
                'value_ms' => $valueMs,
                'tags' => $tags
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::warning("Zamanlama kaydı başarısız: {$name}", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Belirli bir süre için metrik istatistiklerini al
     *
     * @param string $name Metrik adı
     * @param string $type Metrik tipi (counter, timing)
     * @param int $minutes Son kaç dakikalık veriler
     * @return array Metrik istatistikleri
     */
    public function getMetricStats(string $name, string $type = 'timing', int $minutes = 60): array
    {
        try {
            $redisKey = "metrics:{$type}:{$name}";
            
            if ($type === 'timing') {
                // Son belirli dakika içindeki zamanlamalar için min/max/avg hesapla
                $minTime = time() - ($minutes * 60);
                $values = Redis::zrangebyscore($redisKey, $minTime, '+inf');
                
                if (empty($values)) {
                    return [
                        'count' => 0,
                        'min' => null,
                        'max' => null,
                        'avg' => null,
                        'p95' => null
                    ];
                }
                
                $timings = array_map('floatval', $values);
                sort($timings);
                
                // Percentil hesapla (95. percentil)
                $p95Index = (int) ceil(count($timings) * 0.95) - 1;
                $p95 = $timings[$p95Index] ?? max($timings);
                
                return [
                    'count' => count($timings),
                    'min' => min($timings),
                    'max' => max($timings),
                    'avg' => array_sum($timings) / count($timings),
                    'p95' => $p95
                ];
            } elseif ($type === 'counter') {
                // Sayaç değerini al
                $value = Redis::get($redisKey) ?? 0;
                
                return [
                    'value' => (int) $value,
                    'per_minute' => (int) $value / $minutes
                ];
            }
            
            return ['error' => 'Unknown metric type'];
        } catch (\Exception $e) {
            Log::warning("Metrik istatistikleri alınamadı: {$name}", [
                'error' => $e->getMessage()
            ]);
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Aktif metrik adlarını listele
     *
     * @param string $type Metrik tipi (counter, timing, all)
     * @return array Metrik adları listesi
     */
    public function listMetrics(string $type = 'all'): array
    {
        try {
            $pattern = ($type === 'all') ? 'metrics:*' : "metrics:{$type}:*";
            $keys = Redis::keys($pattern);
            
            // Tam anahtarlardan kısa metrik adlarını çıkar
            $metrics = [];
            foreach ($keys as $key) {
                $parts = explode(':', $key);
                if (count($parts) >= 3) {
                    $metricType = $parts[1];
                    $metricName = $parts[2];
                    $metrics[$metricType][] = $metricName;
                }
            }
            
            // Benzersiz metrik adlarını döndür
            if ($type !== 'all') {
                return array_unique($metrics[$type] ?? []);
            }
            
            // Her tip için benzersiz metrik adlarını döndür
            $result = [];
            foreach ($metrics as $metricType => $names) {
                $result[$metricType] = array_unique($names);
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::warning("Metrik listesi alınamadı", [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
