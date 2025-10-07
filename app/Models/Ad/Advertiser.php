<?php

namespace App\Models\Ad;

use App\Casts\DatetimeTz;
use App\Helpers\CommonHelper;
use App\Services\BunnyCdnService;
use App\Observers\AdvertiserObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy(AdvertiserObserver::class)]
/**
 * @mixin IdeHelperAdvertiser
 */
class Advertiser extends Model
{
    public const TYPE_INDIVIDUAL = 1, TYPE_CORPORATE = 2;
    public static array $types = [
        self::TYPE_INDIVIDUAL => 'Bireysel',
        self::TYPE_CORPORATE => 'Kurumsal',
    ];

    public const STATUS_ACTIVE = 1, STATUS_SUSPENDED = 2;
    public static array $statuses = [
        self::STATUS_ACTIVE => 'Aktif',
        self::STATUS_SUSPENDED => 'Askıya Alındı',
    ];

    use SoftDeletes;
    protected $connection = 'pgsql';
    protected $collection = 'advertisers';
    protected $fillable = [
        'logo_path',
        'type_id',
        'name',
        'email',
        'phone',
        'address',
        'status_id',
        'collection_uuid',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => DatetimeTz::class,
            'updated_at' => DatetimeTz::class,
        ];
    }

    public function ads()
    {
        $ads = Ad::where('advertiser_id', $this->id)->get();
        return $ads;
    }


    public function getType(): Attribute
    {
        return Attribute::get(
            fn() => self::$types[$this->type_id] ?? null
        );
    }

    public function getStatus(): Attribute
    {
        return Attribute::get(
            fn() => self::$statuses[$this->status_id] ?? null
        );
    }

    public function getCreatedAt(): Attribute
    {
        return Attribute::get(
            fn() => $this->created_at?->translatedFormat((new CommonHelper)->defaultDateTimeFormat())
        );
    }

    public function logo(): Attribute
    {
        return Attribute::get(function () {
            if (!$this->logo_path)
                return null;

            $bunnyCdnService = app(BunnyCdnService::class);
            return $bunnyCdnService->getStorageUrl($this->logo_path);
        });
    }
}
