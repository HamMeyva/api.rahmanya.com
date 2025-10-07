<?php

namespace App\Models\Ad;

use App\Casts\DatetimeTz;
use MongoDB\Laravel\Eloquent\Model;
use App\Models\Traits\MongoTimestamps;


/**
 * @mixin IdeHelperAdImpression
 */
class AdImpression extends Model
{
    use MongoTimestamps;
    
    protected $connection = 'mongodb';
    protected $collection = 'ad_impressions';

    protected $fillable = [
        'ad_id',
        'user_id',
        'impression_at',
        'ip_address',
        'user_agent',
        'duration',         // Reklama bakılan süre (saniye cinsinden)
        'is_completed',     // Reklamı sonuna kadar izledi mi? (bool)
    ];

    protected function casts(): array
    {
        return [
            'impression_at' => DatetimeTz::class,
            'is_completed' => 'boolean',
        ];
    }
}
