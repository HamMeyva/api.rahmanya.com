<?php

namespace App\Models\Common;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperPage
 */
class Page extends Model
{
    use Sluggable;

    protected $table = 'pages';

    protected $fillable = [
        'cover_image',
        'long_body',
        'short_body',
        'title',
        'is_pinned',
        'slug',
        'menu_show',
        'is_published',
    ];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'title',
                'onUpdate' => true,
            ]
        ];
    }
}
