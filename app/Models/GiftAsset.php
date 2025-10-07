<?php

namespace App\Models;

use App\Models\Gift;
use App\Services\BunnyCdnService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Casts\DatetimeTz;

/**
 * @mixin IdeHelperGiftAsset
 */
class GiftAsset extends Model
{
    protected $connection = 'pgsql';

    protected $table = "gift_assets";

    protected $fillable = [
        'gift_id',
        'team_id',
        'image_path',
        'video_path'
    ];

    protected function casts(): array
    {
        return [
            'created_at' => DatetimeTz::class,
            'updated_at' => DatetimeTz::class,
        ];
    }

    public function gift(): BelongsTo
    {
        return $this->belongsTo(Gift::class);
    }

    public function imageUrl(): Attribute
    {
        return Attribute::get(function () {
            if (!$this->image_path)
                return null;

            $bunnyCdnService = app(BunnyCdnService::class);
            return $bunnyCdnService->getStorageUrl($this->image_path);
        });
    }

    public function videoUrl(): Attribute
    {
        return Attribute::get(function () {
            if (!$this->video_path)
                return null;

            $bunnyCdnService = app(BunnyCdnService::class);
            return $bunnyCdnService->getStorageUrl($this->video_path);
        });
    }
}
