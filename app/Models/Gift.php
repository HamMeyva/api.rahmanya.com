<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Relations\Team;

class Gift extends Model
{
    protected $fillable = [
        'name',
        'image_url',
        'image_path',
        'video_path',
        'coin_value',
        'category',
        'is_active',
        'animation_type',
        'price',
        'slug',
        'is_discount',
        'discounted_price',
        // Frame-by-frame system fields (DEPRECATED)
        'frame_paths',
        'frame_count',
        'animation_duration',
        'frame_rate',
        'is_frame_animation',
        'animation_style',
        'is_video_deprecated',
        // NEW: ZIP-based animation system
        'zip_path',
        'is_zip_animation',
        'zip_frame_count',
        // Team-based filtering
        'team_id'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'coin_value' => 'integer',
        'frame_paths' => 'array',
        'frame_count' => 'integer',
        'animation_duration' => 'integer',
        'frame_rate' => 'integer',
        'is_frame_animation' => 'boolean',
        'is_video_deprecated' => 'boolean',
        // ZIP animation casts
        'is_zip_animation' => 'boolean',
        'zip_frame_count' => 'integer'
    ];

    /**
     * Boot method to automatically sync coin_value and price
     * This ensures coin_value is always set, preventing zero-price bugs
     */
    protected static function boot()
    {
        parent::boot();

        // Before saving (creating or updating), ensure coin_value and price are synced
        static::saving(function ($gift) {
            // If coin_value is missing but price exists, copy price to coin_value
            if (($gift->coin_value === null || $gift->coin_value === 0) && $gift->price > 0) {
                $gift->coin_value = $gift->price;
            }

            // If price is missing but coin_value exists, copy coin_value to price
            if (($gift->price === null || $gift->price === 0) && $gift->coin_value > 0) {
                $gift->price = $gift->coin_value;
            }

            // Ensure slug exists
            if (empty($gift->slug) && !empty($gift->name)) {
                $gift->slug = \Illuminate\Support\Str::slug($gift->name);
            }

            // Set default values
            if ($gift->is_active === null) {
                $gift->is_active = true;
            }

            if ($gift->is_discount === null) {
                $gift->is_discount = false;
            }
        });
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(GiftTransaction::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(GiftAsset::class);
    }

    public function getImageUrlAttribute($value = null): ?string
    {
        // If value is passed directly from database, return it
        if ($value) {
            return $value;
        }

        // First try to get from assets
        $firstAsset = $this->assets()->first();
        if ($firstAsset && $firstAsset->imageUrl) {
            return $firstAsset->imageUrl;
        }

        // Fallback to direct image_path if it exists (use getAttributeValue to avoid recursion)
        $imagePath = $this->getAttributeValue('image_path');
        if ($imagePath) {
            $bunnyCdnService = app(\App\Services\BunnyCdnService::class);
            return $bunnyCdnService->getStorageUrl($imagePath);
        }

        return null;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        if ($category) {
            return $query->where('category', $category);
        }
        return $query;
    }

    /**
     * Filter gifts by team_id
     */
    public function scopeByTeam($query, $teamId)
    {
        if ($teamId === null) {
            return $query->whereNull('team_id');
        }
        return $query->where('team_id', $teamId);
    }

    /**
     * Get the team that owns this gift
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the video URL from the first asset or fallback to video_path
     */
    public function getVideoUrlAttribute(): ?string
    {
        // First try to get from assets
        $firstAsset = $this->assets()->first();
        if ($firstAsset && $firstAsset->videoUrl) {
            return $firstAsset->videoUrl;
        }

        // Fallback to direct video_path if it exists (use getAttributeValue to avoid recursion)
        $videoPath = $this->getAttributeValue('video_path');
        if ($videoPath) {
            $bunnyCdnService = app(\App\Services\BunnyCdnService::class);
            return $bunnyCdnService->getStorageUrl($videoPath);
        }

        return null;
    }

    /**
     * Frame-by-frame sistemi için frame URL'lerini döndür
     */
    public function getFrameUrlsAttribute(): array
    {
        if (!$this->is_frame_animation || empty($this->frame_paths)) {
            return [];
        }

        $bunnyCdnService = app(\App\Services\BunnyCdnService::class);
        $frameUrls = [];

        foreach ($this->frame_paths as $framePath) {
            $frameUrls[] = $bunnyCdnService->getStorageUrl($framePath);
        }

        return $frameUrls;
    }

    /**
     * Frame asset'larından sıralı frame URL'leri döndür
     */
    public function getOrderedFrameUrls(): array
    {
        if (!$this->is_frame_animation) {
            return [];
        }

        $frameAssets = $this->assets()
            ->where('is_frame_asset', true)
            ->orderBy('frame_number')
            ->get();

        $frameUrls = [];
        foreach ($frameAssets as $asset) {
            if ($asset->imageUrl) {
                $frameUrls[] = $asset->imageUrl;
            }
        }

        return $frameUrls;
    }

    /**
     * Frame animasyon verilerini döndür
     */
    public function getFrameAnimationData(): array
    {
        if (!$this->is_frame_animation) {
            return [
                'is_frame_animation' => false,
                'animation_type' => 'video', // Fallback to video
                'video_url' => $this->video_url,
            ];
        }

        $frameUrls = $this->getOrderedFrameUrls();
        if (empty($frameUrls)) {
            $frameUrls = $this->frame_urls;
        }

        return [
            'is_frame_animation' => true,
            'frame_urls' => $frameUrls,
            'frame_count' => $this->frame_count,
            'animation_duration' => $this->animation_duration,
            'frame_rate' => $this->frame_rate,
            'animation_style' => $this->animation_style,
            'animation_type' => 'frame_by_frame',
        ];
    }

    /**
     * Frame asset'ları için relationship
     */
    public function frameAssets(): HasMany
    {
        return $this->assets()->where('is_frame_asset', true)->orderBy('frame_number');
    }

    /**
     * Hediye animasyon modunu kontrol et
     */
    public function isFrameBasedAnimation(): bool
    {
        return $this->is_frame_animation && ($this->frame_count > 0 || !empty($this->frame_paths));
    }

    /**
     * ZIP animasyon URL'ini döndür
     */
    public function getZipUrlAttribute(): ?string
    {
        if (!$this->is_zip_animation || !$this->zip_path) {
            return null;
        }

        $bunnyCdnService = app(\App\Services\BunnyCdnService::class);
        return $bunnyCdnService->getStorageUrl($this->zip_path);
    }

    /**
     * Basit animasyon verilerini döndür (TikTok efektleri yok)
     */
    public function getSimpleAnimationData(): array
    {
        // ZIP tabanlı sistem varsa onu kullan
        if ($this->is_zip_animation && $this->zip_path) {
            return [
                'animation_type' => 'zip',
                'zip_url' => $this->zip_url,
                'frame_count' => $this->zip_frame_count ?? 0,
                'animation_duration' => $this->animation_duration ?? 3000,
                'animation_style' => 'simple', // Sadece basit, merkez oynama
                'mobile_optimized' => true, // Flutter için optimize edildi
                'compression_level' => 3, // Hızlı açılma için düşük seviye
            ];
        }

        // Fallback: Frame-by-frame sistem
        if ($this->is_frame_animation) {
            $frameUrls = $this->getOrderedFrameUrls();
            if (empty($frameUrls)) {
                $frameUrls = $this->frame_urls;
            }

            return [
                'animation_type' => 'frames',
                'frame_urls' => $frameUrls,
                'frame_count' => $this->frame_count,
                'animation_duration' => $this->animation_duration,
                'animation_style' => 'simple', // Basit stil
            ];
        }

        // Fallback: Video sistem
        return [
            'animation_type' => 'video',
            'video_url' => $this->video_url,
            'animation_style' => 'simple',
        ];
    }

    /**
     * @deprecated Use getSimpleAnimationData() instead
     * TikTok benzeri animasyon verilerini döndür
     */
    public function getTikTokAnimationData(): array
    {
        return $this->getSimpleAnimationData(); // Redirect to simple version
    }

    /**
     * Get the final price of the gift (considering discounts)
     * This accessor allows using $gift->final_price or $gift->get_final_price
     */
    public function getFinalPriceAttribute(): int
    {
        // If discount is active and discounted_price is set, use it
        if ($this->is_discount && $this->discounted_price !== null && $this->discounted_price > 0) {
            return $this->discounted_price;
        }

        // First try coin_value if it's set (new system)
        if ($this->coin_value !== null && $this->coin_value > 0) {
            return $this->coin_value;
        }

        // Fallback to price (legacy system)
        return $this->price ?? 0;
    }
}
