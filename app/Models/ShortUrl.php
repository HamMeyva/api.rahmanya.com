<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperShortUrl
 */
class ShortUrl extends Model
{
    protected $table = 'short_urls';

    protected $fillable = [
        'short_code',
        'original_url'
    ];

}
