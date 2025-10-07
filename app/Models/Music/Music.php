<?php

namespace App\Models\Music;

use App\Models\Music\Artist;
use App\Observers\MusicObserver;
use App\Services\BunnyCdnService;
use App\Models\Music\MusicCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use App\Casts\DatetimeTz;

#[ObservedBy(MusicObserver::class)]
/**
 * @mixin IdeHelperMusic
 */
class Music extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'musics';

    protected $fillable = [
        'title',
        'slug',
        'artist_id',
        'music_category_id',
        'music_path',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => DatetimeTz::class,
            'updated_at' => DatetimeTz::class,
        ];
    }
    protected $appends = ['music_url'];

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MusicCategory::class, 'music_category_id');
    }

    public function musicUrl(): Attribute
    {
        return Attribute::get(function () {
            if (!$this->music_path)
                return null;

            $bunnyCdnService = app(BunnyCdnService::class);
            return $bunnyCdnService->getStorageUrl($this->music_path);
        });
    }
}
