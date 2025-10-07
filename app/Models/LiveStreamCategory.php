<?php

namespace App\Models;

use App\Models\Agora\AgoraChannel;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Casts\DatetimeTz;

/**
 * @mixin IdeHelperLiveStreamCategory
 */
class LiveStreamCategory extends Model
{
    use HasFactory, SoftDeletes, Sluggable;

    protected $connection = 'pgsql';

    protected $table = 'live_stream_categories';

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'description',
        'cover_image',
        'is_active',
        'parent_id',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'created_at' => DatetimeTz::class,
            'updated_at' => DatetimeTz::class,
            'deleted_at' => DatetimeTz::class,
        ];
    }

    protected $appends = ['icon_url'];


    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name',
                'onUpdate' => true,
            ]
        ];
    }


    public function parent(): BelongsTo
    {
        return $this->belongsTo(LiveStreamCategory::class, 'parent_id');
    }

    public function subcategories(): HasMany
    {
        return $this->hasMany(LiveStreamCategory::class, 'parent_id');
    }

    public function streams(): HasMany
    {
        return $this->hasMany(AgoraChannel::class, 'category_id');
    }


    public function scopeActive($query)
    {
        return $query->whereRaw('"is_active" = TRUE');
    }

    public function scopeMainCategories($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order', 'asc')->orderBy('name', 'asc');
    }

    public function scopeByParent($query, int $parentId)
    {
        return $query->where('parent_id', $parentId);
    }

    public function getFullPathAttribute(): string
    {
        $path = $this->name;
        $currentCategory = $this;

        while ($parent = $currentCategory->parent) {
            $path = $parent->name . ' > ' . $path;
            $currentCategory = $parent;
        }

        return $path;
    }

    public function getSubcategoriesCountAttribute(): int
    {
        return $this->subcategories()->count();
    }

    public function getActiveStreamsCountAttribute(): int
    {
        return $this->streams()->active()->count();
    }

    public function iconUrl(): Attribute
    {
        return Attribute::get(function () {
            return $this->icon ? asset("storage/$this->icon") : null;
        });
    }
}
