<?php

namespace App\Models\Common;

use App\Models\Common\City;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperDistrict
 */
class District extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
