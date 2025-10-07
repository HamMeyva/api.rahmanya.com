<?php

namespace App\Console\Commands\Ad;

use Exception;
use App\Models\Ad\Ad;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class FlushAdMetricsToAdsTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:flush-ad-metrics-to-ads-table {--batch-size=50 : Number of ads to process in a batch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rediste tutulan reklam metriklerini reklam tablosuna aktarır.';

    /**
     * Cache TTL for ad metrics
     */
    const CACHE_TTL = 3600; // 1 hour

    /**
     * Maximum number of database operations in one run
     */
    const MAX_DB_OPERATIONS = 200;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $batchSize = (int)$this->option('batch-size');
        $dbOperationCount = 0;
        $processedCount = 0;

        // Use scan instead of keys for better performance with large datasets
        $iterator = null;
        $pattern = 'ad:*:metrics';
        $adUpdates = [];

        do {
            // Get a batch of keys using SCAN instead of KEYS
            $scanResult = Redis::scan($iterator, ['match' => $pattern, 'count' => $batchSize]);

            if ($scanResult === false) {
                break;
            }

            [$iterator, $keys] = $scanResult;

            if (empty($keys)) {
                continue;
            }

            Log::debug('FlushAdMetrics: Processing batch', ['batch_size' => count($keys)]);

            // Prepare processing keys and perform atomic operations
            $processingKeys = [];
            $adIds = [];

            foreach ($keys as $key) {
                $processingKey = $key . ':processing';
                $processingKeys[$key] = $processingKey;

                // Extract ad ID for later use
                $adId = str_replace(['ad:', ':metrics'], '', $key);
                $adIds[$key] = $adId;
            }

            // Process each key with optimized Redis operations
            foreach ($keys as $key) {
                $processingKey = $processingKeys[$key];
                $adId = $adIds[$key];

                // Skip if we've reached the DB operation limit
                if ($dbOperationCount >= self::MAX_DB_OPERATIONS) {
                    break;
                }

                // Use a transaction to ensure atomicity
                Redis::transaction(function ($redis) use ($key, $processingKey, $adId, &$adUpdates, &$dbOperationCount) {
                    // Check if key exists and rename it atomically
                    if (!$redis->exists($key)) {
                        return;
                    }

                    try {
                        $redis->rename($key, $processingKey);

                        // Get metrics data
                        $metrics = $redis->hmget($processingKey, ['impressions', 'clicks']);
                        $impressions = (int) $metrics[0];
                        $clicks = (int) $metrics[1];

                        // Only process if there's data to update
                        if ($impressions > 0 || $clicks > 0) {
                            $adUpdates[] = [
                                'id' => $adId,
                                'impressions' => $impressions,
                                'clicks' => $clicks,
                                'processing_key' => $processingKey
                            ];
                            $dbOperationCount++;
                        } else {
                            // No metrics to update, just clean up
                            $redis->del($processingKey);
                        }
                    } catch (Exception $e) {
                        Log::debug('FlushAdMetrics: Key rename failed', ['key' => $key, 'error' => $e->getMessage()]);
                    }
                });
            }

            // Process ad updates in batches
            if (!empty($adUpdates)) {
                // Get all ad IDs to fetch in one query
                $adIdsToFetch = array_column($adUpdates, 'id');

                // Fetch all ads in one query
                $ads = Ad::whereIn('_id', $adIdsToFetch)->get()->keyBy('_id');

                // Process each update
                foreach ($adUpdates as $update) {
                    $adId = $update['id'];
                    $impressions = $update['impressions'];
                    $clicks = $update['clicks'];
                    $processingKey = $update['processing_key'];

                    if (!isset($ads[$adId])) {
                        // Ad not found, just clean up
                        Redis::del($processingKey);
                        continue;
                    }

                    $ad = $ads[$adId];

                    // Update ad metrics
                    if ($impressions > 0) {
                        $ad->impressions = ($ad->impressions ?? 0) + $impressions;
                    }

                    if ($clicks > 0) {
                        $ad->clicks = ($ad->clicks ?? 0) + $clicks;
                    }

                    // Calculate CTR
                    if ($ad->impressions > 0) {
                        $ad->ctr = ($ad->clicks / $ad->impressions) * 100;
                    }

                    // Save changes
                    $ad->save();
                    $processedCount++;

                    // Clean up processing key
                    Redis::del($processingKey);
                }

                // Clear the updates array for the next batch
                $adUpdates = [];
            }

        } while ($iterator != 0 && $dbOperationCount < self::MAX_DB_OPERATIONS);

        if ($processedCount > 0) {
            $this->info("Processed metrics for {$processedCount} ads");
        } else {
            $this->info("No ad metrics to process");
        }

        return 0;
    }
}
