<?php

namespace App\Models\Ad;

use App\Casts\DatetimeTz;
use App\Models\Common\City;
use App\Helpers\CommonHelper;
use App\Observers\AdObserver;
use App\Models\Common\Country;
use App\Models\Demographic\Os;
use App\Models\Relations\Team;
use App\Services\BunnyCdnService;
use App\Models\Demographic\Gender;
use App\Models\Traits\PaymentTrait;
use App\Models\Demographic\AgeRange;
use App\Models\Demographic\Language;
use App\Models\Demographic\Placement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[ObservedBy(AdObserver::class)]
/**
 * @mixin IdeHelperAd
 */
class Ad extends Model
{
    public const STATUS_ACTIVE = 1, STATUS_INACTIVE = 2;
    public static array $statuses = [
        self::STATUS_ACTIVE => 'Aktif',
        self::STATUS_INACTIVE => 'Pasif',
    ];

    public static array $statusColors = [
        self::STATUS_ACTIVE => 'success',
        self::STATUS_INACTIVE => 'danger',
    ];

    public const PAYMENT_STATUS_PENDING = 1,
        PAYMENT_STATUS_COMPLETED = 2;
    public static array $paymentStatuses = [
        self::PAYMENT_STATUS_PENDING => 'Ödeme Bekliyor',
        self::PAYMENT_STATUS_COMPLETED => 'Ödeme Tamamlandı',
    ];
    public static array $paymentStatusColors = [
        self::PAYMENT_STATUS_PENDING => 'warning',
        self::PAYMENT_STATUS_COMPLETED => 'success',
    ];

    public const MEDIA_TYPE_IMAGE = 1, MEDIA_TYPE_VIDEO = 2;
    public static array $mediaTypes = [
        self::MEDIA_TYPE_IMAGE => 'Resim',
        self::MEDIA_TYPE_VIDEO => 'Video',
    ];

    use SoftDeletes, PaymentTrait;
    protected $connection = 'pgsql';
    protected $table = 'ads';
    protected $fillable = [
        'advertiser_id',
        'title',
        'description',
        'redirect_url',
        'media_type_id',
        'media_path',
        'video_guid',
        'status_id',
        'payment_status_id',
        'paid_at',
        'start_date',
        'show_start_time',
        'show_end_time',
        'total_budget',
        'total_hours',
        'bid_amount',
        'target_country_id',
        'target_language_id',

        'impressions',            // Gösterim sayısı
        'clicks',                 // Tıklama sayısı
        'ctr',                    // Tıklama oranı
    ];

    protected function casts(): array
    {
        return [
            'created_at' => DatetimeTz::class,
            'updated_at' => DatetimeTz::class,
            'paid_at' => DatetimeTz::class,
            'start_date' => 'date',
            'show_start_time' => 'datetime:H:i',
            'show_end_time' => 'datetime:H:i',
        ];
    }

    protected $appends = [
        'media_url',
        'thumbnail_url',
    ];


    /* start::Relations */
    public function advertiser(): BelongsTo
    {
        return $this->belongsTo(Advertiser::class);
    }

    public function target_country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'target_country_id');
    }

    public function target_language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'target_language_id');
    }

    public function placements(): BelongsToMany
    {
        return $this->belongsToMany(Placement::class, 'ad_placement', 'ad_id', 'placement_id')->withTimestamps();
    }

    public function target_cities(): BelongsToMany
    {
        return $this->belongsToMany(City::class, 'ad_target_city', 'ad_id', 'city_id')->withTimestamps();
    }

    public function target_age_ranges(): BelongsToMany
    {
        return $this->belongsToMany(AgeRange::class, 'ad_target_age_range', 'ad_id', 'age_range_id')->withTimestamps();
    }

    public function target_genders(): BelongsToMany
    {
        return $this->belongsToMany(Gender::class, 'ad_target_gender', 'ad_id', 'gender_id')->withTimestamps();
    }

    public function target_teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'ad_target_team', 'ad_id', 'team_id')->withTimestamps();
    }

    public function target_oses(): BelongsToMany
    {
        return $this->belongsToMany(Os::class, 'ad_target_os', 'ad_id', 'os_id')->withTimestamps();
    }
    /* end::Relations */


    /* start::Attributes */
    public function getCreatedAt(): Attribute
    {
        return Attribute::get(
            fn() => $this->created_at?->translatedFormat((new CommonHelper)->defaultDateTimeFormat(true))
        );
    }
    public function getStartDate(): Attribute
    {
        return Attribute::get(
            fn() => $this->start_date?->translatedFormat((new CommonHelper)->defaultDateFormat())
        );
    }
    public function getStatus(): Attribute
    {
        return Attribute::get(
            fn() => self::$statuses[$this->status_id] ?? null
        );
    }
    public function getStatusColor(): Attribute
    {
        return Attribute::get(fn() => self::$statusColors[$this->status_id] ?? 'secondary');
    }

    public function getPaymentStatus(): Attribute
    {
        return Attribute::get(
            fn() => self::$paymentStatuses[$this->payment_status_id] ?? null
        );
    }
    public function getPaymentStatusColor(): Attribute
    {
        return Attribute::get(fn() => self::$paymentStatusColors[$this->payment_status_id] ?? 'secondary');
    }

    public function getMediaType(): Attribute
    {
        return Attribute::get(
            fn() => self::$mediaTypes[$this->media_type_id] ?? null
        );
    }

    public function getTotalBudget(): Attribute
    {
        return Attribute::get(function () {
            return number_format($this->total_budget, 2, ',', '.');
        });
    }
    public function drawTotalBudget(): Attribute
    {
        return Attribute::get(function () {
            return  "₺{$this->get_total_budget}";
        });
    }
    public function drawTotalHours(): Attribute
    {
        return Attribute::get(fn() => "{$this->total_hours} saat");
    }

    public function getBidAmount(): Attribute
    {
        return Attribute::get(fn() => $this->bid_amount ? number_format($this->bid_amount, 2, ',', '.') : 0);
    }
    public function drawBidAmount(): Attribute
    {
        return Attribute::get(fn() => "₺{$this->get_bid_amount}");
    }

    public function mediaUrl(): Attribute
    {
        return Attribute::get(function () {
            if ($this->media_type_id == self::MEDIA_TYPE_VIDEO) {
                if (!$this->video_guid) return null;

                return app(BunnyCdnService::class)->getStreamUrl($this->video_guid);
            }else{
                if (!$this->media_path) return null;

                $bunnyCdnService = app(BunnyCdnService::class);
                return $bunnyCdnService->getStorageUrl($this->media_path);
            }
        });
    }

    public function thumbnailUrl(): Attribute
    {
        return Attribute::get(function () {
            if ($this->media_type_id == self::MEDIA_TYPE_VIDEO) {
                if (!$this->video_guid) return null;

                return app(BunnyCdnService::class)->getThumbnailUrl($this->video_guid);
            }else{
                if (!$this->media_path) return null;

                $bunnyCdnService = app(BunnyCdnService::class);
                return $bunnyCdnService->getStorageUrl($this->media_path);
            }
        });
    }
    /* end::Attributes */
}
