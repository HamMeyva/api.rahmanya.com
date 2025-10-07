<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperCurrency
 */
class Currency extends Model
{
    protected $fillable = [
        'code',
        'symbol',
        'name',
    ];
}
