<?php

namespace App\Services;

use Exception;
use App\Models\Ad\Ad;
use App\Models\AppSetting;
use Illuminate\Support\Facades\Log;
use App\Models\Demographic\AgeRange;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class AdService
{
    protected $seenCachePrefix = 'user_seen_ads_';

    /* 
        videoCount => video sayısına göre reklam sayısını hesaplıyoruz
    */
    public function getAds($user, $options = [])
    {
        $userId = $user->id;

        $videoCount = $options['video_count'] ?? 50;

        $adInterval = AppSetting::getSetting('ad_interval');
        $adsNeeded = floor($videoCount / $adInterval);

        $placementIds = $options['placement_ids'] ?? [];
        $osIds = $options['os_ids'] ?? [];

        // Yeni reklamları cache’ten al ya da DB’den çek
        $ads = Cache::remember("ads_pool", 3600, function () use ($user, $placementIds, $osIds) {
            return Ad::query()
                ->with(['placements', 'target_cities', 'target_age_ranges', 'target_genders', 'target_teams', 'target_oses'])
                ->where('status_id', Ad::STATUS_ACTIVE)
                ->orderBy('bid_amount', 'desc')
                ->get();
        });

        // kullanıcıya uygun reklamları filtrele
        $filteredAds = $ads->filter(function ($ad) use ($user, $placementIds, $osIds) {

            if ($ad->target_country_id !== $user->country_id) return false;

            if ($ad->target_language_id !== $user->preferred_language_id) return false;

            //if (!$ad->placements->pluck('placement_id')->intersect($placementIds)->count()) return false;

            if ($ad->target_cities->isNotEmpty() && !$ad->target_cities->pluck('city_id')->contains($user->city_id)) {
                return false;
            }

            $userAgeRangeId = AgeRange::getAgeRangeByAge($user->age);
            if ($ad->target_age_ranges->isNotEmpty() && !$ad->target_age_ranges->pluck('age_range_id')->contains($userAgeRangeId)) {
                return false;
            }

            if ($ad->target_genders->isNotEmpty() && !$ad->target_genders->pluck('gender_id')->contains($user->gender_id)) {
                return false;
            }

            if ($ad->target_teams->isNotEmpty() && !$ad->target_teams->pluck('team_id')->contains($user->primary_team_id)) {
                return false;
            }

            if ($ad->target_oses->isNotEmpty() && !$ad->target_oses->pluck('os_id')->intersect($osIds)->count()) {
                return false;
            }

            return true;
        });

        // Kullanıcının daha önce gördüğü reklamları al
        $seenAdIds = $this->getSeenAdIds($userId);

        // Daha önce izlenen reklamları çıkar
        $newAds = $ads->whereNotIn('id', $seenAdIds)->take($adsNeeded);

        // Eğer yeterli yeni reklam yoksa (fallback)
        if ($newAds->count() < $adsNeeded) {
            $remaining = $adsNeeded - $newAds->count();

            $fallbackAds = $ads->whereIn('id', $seenAdIds)
                ->shuffle()
                ->take($remaining);

            $finalAds = $newAds->concat($fallbackAds);
        } else {
            $finalAds = $newAds;
        }

        Log::info("Returned `{$finalAds->count()}` ads for user `#{$userId}`.");

        return $finalAds->values();
    }

    //redise yazıyoruz
    public function markAdAsSeen(string $userId, int $adId): void
    {
        $key = $this->seenCachePrefix . $userId;
        $now = now()->timestamp;

        // Reklamı timestamp ile ekle
        Redis::zadd($key, $now, $adId);

        // Eski 12 saatten önceki izlenmeleri temizle (12 saat önce izlediği reklamı tekrar izleyebilmesi için)
        $cutoff = now()->subHours(12)->timestamp;
        Redis::zremrangebyscore($key, 0, $cutoff);
    }

    public function getSeenAdIds(string $userId): array
    {
        $key = $this->seenCachePrefix . $userId;
        $cutoff = now()->subDay()->timestamp;

        return Redis::zrangebyscore($key, $cutoff, '+inf');
    }

    public function resetSeenAds(string $userId): void
    {
        Redis::del($this->seenCachePrefix . $userId);
    }
}
