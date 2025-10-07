<?php

namespace App\Models\Relations;

use Illuminate\Database\Eloquent\Model;
use App\Observers\TeamObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Support\Facades\Storage;
use App\Casts\DatetimeTz;

/**
 * @mixin IdeHelperTeam
 */

#[ObservedBy(TeamObserver::class)]
class Team extends Model
{
    protected $table = 'teams';

    protected $fillable = [
        'name',
        'logo',
        'colors',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => DateTimeTz::class,
            'updated_at' => DateTimeTz::class,
            'colors' => 'array',
        ];
    }

    public function getLogoAttribute(): ?string
    {
        $value = $this->attributes['logo'];

        if (empty($value)) {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        return Storage::disk('public')->url($value);
    }
}
