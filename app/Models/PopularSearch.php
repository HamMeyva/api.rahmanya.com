<?php

namespace App\Models;

use App\Services\BunnyCdnService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Casts\DatetimeTz;

/**
 * @mixin IdeHelperPopularSearch
 */
class PopularSearch extends Model
{
    protected $fillable = [
        'title',
        'image_path',
        'is_active',
        'queue'
    ];

    protected $appends = ['image_url'];

    protected function casts(): array
    {
        return [
            'created_at' => DatetimeTz::class,
            'updated_at' => DatetimeTz::class,
            'is_active' => 'boolean',   
        ];
    }

    public function imageUrl(): Attribute
    {
        return Attribute::get(function () {
            if (!$this->image_path)
                return null;

            $bunnyCdnService = app(BunnyCdnService::class);
            return $bunnyCdnService->getStorageUrl($this->image_path);
        });
    }
}
