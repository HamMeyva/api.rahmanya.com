<?php

namespace App\Models\Music;

use App\Models\Music\Music;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Cviebrock\EloquentSluggable\Sluggable;
use App\Casts\DatetimeTz;

/**
 * @mixin IdeHelperArtist
 */
class Artist extends Model
{
    use Sluggable;

    protected $connection = 'pgsql';
    protected $table = 'artists';

    protected $fillable = [
        'name',
        'slug',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => DatetimeTz::class,
            'updated_at' => DatetimeTz::class,
        ];
    }

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name',
                'onUpdate' => true,
            ],
        ];
    }

    public function musics(): HasMany
    {
        return $this->hasMany(Music::class);
    }
}
