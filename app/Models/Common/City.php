<?php

namespace App\Models\Common;

use App\Models\Common\Country;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperCity
 */
class City extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function districts(): HasMany
    {
        return $this->hasMany(District::class)->orderBy("name");
    }
}
