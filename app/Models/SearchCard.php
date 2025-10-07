<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use MongoDB\Laravel\Eloquent\Model;
use App\Casts\DatetimeTz;   
use App\Models\Traits\MongoTimestamps;

/**
 * @mixin IdeHelperSearchCard
 */
class SearchCard extends Model
{
    use HasFactory, SoftDeletes, MongoTimestamps;

    protected $connection = 'mongodb';
    protected $collection = 'search_cards';
    protected $guarded = [];

    protected $fillable = [
        'title',
        'image_url',
        'search_query',
        'order',
        'is_active',
        'click_count',
        'category',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
        'click_count' => 'integer',
    ];

    /**
     * Aktif kartları getir
     * 
     * @return \MongoDB\Laravel\Eloquent\Builder
     */
    public static function active()
    {
        return static::query()->where('is_active', true)
            ->orderBy('order', 'asc')
            ->orderBy('click_count', 'desc');
    }

    /**
     * Tıklanma sayısını artır
     * 
     * @return bool
     */
    public function incrementClickCount()
    {
        $this->click_count = ($this->click_count ?? 0) + 1;
        return $this->save();
    }
}
