<?php

namespace App\Models\Ad;

use App\Casts\DatetimeTz;
use MongoDB\Laravel\Eloquent\Model;
use App\Models\Traits\MongoTimestamps;

/**
 * @mixin IdeHelperAdClick
 */
class AdClick extends Model
{
    use MongoTimestamps;
    
    protected $connection = 'mongodb';
    protected $collection = 'ad_clicks';

    protected $fillable = [
        'ad_id',
        'user_id',
        'click_at',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'click_at' => DatetimeTz::class,
        ];
    }
}