<?php

namespace App\Models\Morph;

use App\Models\User;
use App\Models\Admin;
use App\Helpers\CommonHelper;
use Illuminate\Database\Eloquent\Model;
use App\Models\Fake\ReportProblemCategory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Casts\DatetimeTz;

/**
 * @mixin IdeHelperReportProblem
 */
class ReportProblem extends Model
{
    public static array $entityTypes = [
        'Video' => 'Video',
        'Story' => 'Hikaye',
        'User' => 'Kullanıcı',
        'AgoraChannel' => 'Canlı Yayın',
        'GeneralProblem' => 'Sorun Bildir'
    ];

    public const
        STATUS_PENDING = 1,
        STATUS_REVIEWED = 2,
        STATUS_ACTIONED = 3,
        STATUS_REJECTED = 4;

    public static array $statuses = [
        self::STATUS_PENDING => 'Bekliyor',
        self::STATUS_REVIEWED => 'İncelendi',
        self::STATUS_ACTIONED => 'İşlem Yapıldı',
        self::STATUS_REJECTED => 'Reddedildi',
    ];

    public static array $statusColors = [
        self::STATUS_PENDING => '#3490dc',
        self::STATUS_REVIEWED => '#52c41a',
        self::STATUS_ACTIONED => '#1890ff',
        self::STATUS_REJECTED => '#ff4d4f',
    ];

    protected $table = 'report_problems';

    protected $connection = 'pgsql';

    protected $fillable = [
        'entity_type',
        'entity_id',
        'user_id',
        'status_id',
        'report_problem_category_id',
        'message',
        'admin_id',
        'admin_response',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => DatetimeTz::class,
            'updated_at' => DatetimeTz::class,
        ];
    }

    protected $appends = [
        'get_status',
        'get_status_color',
        'get_entity_type',
        'get_created_at',
        'get_entity_url',
    ];

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    public function report_problem_category(): BelongsTo
    {
        return $this->belongsTo(ReportProblemCategory::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }


    public function getStatus(): Attribute
    {
        return Attribute::get(
            fn() => self::$statuses[$this->status_id] ?? null
        );
    }

    public function getStatusColor(): Attribute
    {
        return Attribute::get(
            fn() => self::$statusColors[$this->status_id] ?? null
        );
    }

    public function getEntityType(): Attribute
    {
        return Attribute::get(fn() => self::$entityTypes[$this->entity_type] ?? null);
    }

    public function getCreatedAt(): Attribute
    {
        return Attribute::get(
            fn() => $this->created_at->translatedFormat((new CommonHelper)->defaultDateTimeFormat())
        );
    }

    public function getEntityUrl(): Attribute
    {
        return Attribute::get(function () {
            return match ($this->entity_type) {
                'Video' => route('admin.videos.show', ['id' => $this->entity_id]),
                'Story' => null,
                'User' => route('admin.users.show', ['id' => $this->entity_id]),
                'AgoraChannel' => route('admin.live-streams.show', ['id' => $this->entity_id]),
                'GeneralProblem' => null,
                default => null,
            };
        });
    }
}
