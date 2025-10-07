<?php

namespace App\Models;

use App\Models\GiftAsset;
use App\Models\Relations\Team;
use App\Services\BunnyCdnService;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Casts\DatetimeTz;

/**
 * @mixin IdeHelperGift
 */
class Gift extends Model
{
    use Sluggable;

    protected $connection = 'pgsql';

    protected $table = "gifts";

    protected $fillable = [
        'is_active',
        'name',
        'slug',
        'price',
        'is_discount',
        'discounted_price',
        'is_custom_gift',
        'queue',
        'total_usage',
        'total_sales',
        'has_variants',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => DatetimeTz::class,
            'updated_at' => DatetimeTz::class,
            'is_active' => 'boolean',
            'has_variants' => 'boolean',
            'is_discount' => 'boolean',
            'is_custom_gift' => 'boolean',
        ];
    }

    protected $appends = ['get_final_price'];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name',
                'onUpdate' => true,
            ],
        ];
    }

    public function assets(): HasMany
    {
        return $this->hasMany(GiftAsset::class);
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

    public function getFinalPrice(): Attribute
    {
        return Attribute::get(function () {
            return $this->is_discount ? $this->discounted_price : $this->price;
        });
    }
}
