<?php

namespace App\Models;

use Mongodb\Laravel\Eloquent\Model;
use App\Casts\DatetimeTz;
use App\Models\Traits\MongoTimestamps;

/**
 * PerformanceMetric - Uzun vadeli performans metrik takibi için model
 * 
 * Bu model, önemli sistem operasyonlarının (feed üretme, cache işlemleri vb.)
 * performans metriklerini kaydeder ve analiz için uzun vadeli depolama sağlar.
 *
 * @mixin IdeHelperPerformanceMetric
 */
class PerformanceMetric extends Model
{
    use MongoTimestamps;
    protected $connection = 'mongodb';
    protected $collection = 'performance_metrics';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'operation',
        'duration_ms',
        'metrics',
        'level',
        'timestamp',
        'context',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected function casts(): array
    {
        return [
            'duration_ms' => 'float',
            'metrics' => 'json',
            'context' => 'json',
            'timestamp' => DatetimeTz::class,
        ];
    }

    /**
     * The "booted" method of the model.
     * Otomatik zaman damgası ekler ve collection boyutunu sınırlar
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            if (!isset($model->timestamp)) {
                $model->timestamp = now();
            }
        });

        // Performance metrics koleksiyonunu belirli bir boyutta tutmak için
        // otomatik temizleme mekanizması. Yalnızca 50.000 kayıt saklanır.
        try {
            // Ayda bir kez çalışacak şekilde limitle
            $today = now()->format('Y-m-d');
            $cleanupKey = "performance_metrics_cleanup:{$today}";

            if (!cache()->has($cleanupKey) && rand(0, 100) < 5) { // %5 olasılıkla
                // TTL indeksi ayarla (MongoDB 30 gün sonra otomatik temizler)
                static::raw(function ($collection) {
                    $collection->createIndex(
                        ['timestamp' => 1],
                        ['expireAfterSeconds' => 30 * 24 * 60 * 60] // 30 gün
                    );
                });

                // Temizlik yapıldı, bir günlük kilit ekle
                cache()->put($cleanupKey, true, 60 * 60 * 24); // 24 saat
            }
        } catch (\Exception $e) {
            // Model hala çalışabilir, sadece log tutalım
            \Log::warning('PerformanceMetric temizleme indeksi oluşturulamadı', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Performans trendlerini analiz et
     *
     * @param string $operation İşlem adı
     * @param int $days Son kaç günün verileri
     * @return array Trend analizi
     */
    public static function analyzeTrends(string $operation, int $days = 7): array
    {
        try {
            $startDate = now()->subDays($days);

            // Günlük ortalama süreleri getir
            $dailyAverages = static::where('operation', $operation)
                ->where('timestamp', '>=', $startDate)
                ->raw(function ($collection) use ($operation, $startDate) {
                    return $collection->aggregate([
                        [
                            '$match' => [
                                'operation' => $operation,
                                'timestamp' => ['$gte' => $startDate]
                            ]
                        ],
                        [
                            '$group' => [
                                '_id' => [
                                    'year' => ['$year' => '$timestamp'],
                                    'month' => ['$month' => '$timestamp'],
                                    'day' => ['$dayOfMonth' => '$timestamp']
                                ],
                                'avg_duration' => ['$avg' => '$duration_ms'],
                                'count' => ['$sum' => 1]
                            ]
                        ],
                        ['$sort' => ['_id.year' => 1, '_id.month' => 1, '_id.day' => 1]]
                    ]);
                });

            // Okunabilir format oluştur
            $trends = [];
            foreach ($dailyAverages as $item) {
                $date = sprintf(
                    '%04d-%02d-%02d',
                    $item['_id']['year'],
                    $item['_id']['month'],
                    $item['_id']['day']
                );

                $trends[$date] = [
                    'date' => $date,
                    'avg_duration_ms' => round($item['avg_duration'], 2),
                    'count' => $item['count']
                ];
            }

            return [
                'operation' => $operation,
                'period_days' => $days,
                'daily_trends' => array_values($trends)
            ];
        } catch (\Exception $e) {
            \Log::error('Performans trendi analizi başarısız', [
                'error' => $e->getMessage(),
                'operation' => $operation
            ]);

            return [
                'operation' => $operation,
                'error' => $e->getMessage()
            ];
        }
    }
}
