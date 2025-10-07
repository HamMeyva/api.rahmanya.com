<?php

namespace App\Casts;

use App\Services\Timezone;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Facades\Cache;

class DatetimeTz implements CastsAttributes
{
    /**
     * Cache TTL in seconds (1 hour)
     */
    const CACHE_TTL = 3600;

    /**
     * Get the attribute from the database and convert to the application timezone
     * Uses caching to avoid repeated expensive timezone conversions
     */
    public function get($model, string $key, $value, array $attributes)
    {
        if (!$value) {
            return null;
        }

        $tz = Timezone::get();
        $cacheKey = "datetime_tz_{$value}_{$tz}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($value, $tz) {
            return Carbon::parse($value)->tz($tz);
        });
    }

    /**
     * Prepare the given value for storage.
     */
    public function set($model, string $key, $value, array $attributes)
    {
        return $value;
    }
}
