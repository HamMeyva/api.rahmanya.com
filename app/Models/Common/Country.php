<?php

namespace App\Models\Common;

use App\Models\Common\City;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


/**
 * @mixin IdeHelperCountry
 */
class Country extends Model
{
    public $timestamps = false;

    public function cities(): HasMany
    {
        return $this->hasMany(City::class)->orderBy("code");
    }
}
