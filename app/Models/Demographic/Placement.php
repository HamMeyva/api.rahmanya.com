<?php

namespace App\Models\Demographic;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperPlacement
 */
class Placement extends Model
{
    public const PLACEMENT_MIXED_FEED = 1, PLACEMENT_FOLLOWED_FEED = 2, PLACEMENT_SPORT_FEED = 3;

    public static array $placements = [
        self::PLACEMENT_MIXED_FEED => 'Karışık Akış',
        self::PLACEMENT_FOLLOWED_FEED => 'Takip Edilenler Akışı',
        self::PLACEMENT_SPORT_FEED => 'Sporcular Akışı',
    ];
    
    protected $connection = 'pgsql';
    protected $table = 'placements';
    protected $fillable = [
        'name',
    ];
}
