<?php

namespace App\Models\Common;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperFaq
 */
class Faq extends Model
{
    use Sluggable;

    protected $table = 'faqs';

    protected $fillable = [
        'answer',
        'question',
        'name',
        'is_published',
    ];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name',
                'onUpdate' => true,
            ],
        ];
    }
}
