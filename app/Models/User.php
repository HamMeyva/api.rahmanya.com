<?php

namespace App\Models;

use Carbon\Carbon;
use App\Casts\DatetimeTz;
use App\Models\GiftBasket;
use App\Models\Common\City;
use App\Helpers\CommonHelper;
use App\Models\Common\Country;
use App\Models\Relations\Team;
use App\Models\UserPunishment;
use App\Models\UserApprovalLog;
use App\Models\UserDeviceLogin;
use App\Observers\UserObserver;
use App\Models\Chat\Conversation;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Agora\AgoraChannel;
use App\Models\Demographic\Gender;
use App\Models\Demographic\AgeRange;
use App\Models\Relations\UserDevice;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use App\Models\Traits\UsersCommonTrait;
use Illuminate\Support\Facades\Storage;
use Illuminate\Notifications\Notifiable;
use App\Models\Traits\ReportProblemTrait;
use App\Models\Coin\CoinWithdrawalRequest;
use App\Models\Relations\UserCoinTransaction;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\UserRoutesNotificationsChannels;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * @mixin IdeHelperUser
 */
#[ObservedBy(UserObserver::class)]
class User extends Authenticatable
{
    use Notifiable,
        HasApiTokens,
        UsersCommonTrait,
        ReportProblemTrait,
        HasUuids,
        SoftDeletes,
        UserRoutesNotificationsChannels;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'surname',
        'nickname',
        'birthday',
        'gender_id',
        'phone',
        'email',
        'password',
        'phone_verified_at',
        'email_verified_at',
        'bio',
        'slogan',
        'avatar',
        'primary_team_id',
        'remember_token',
        'preferred_language_id',
        'latitude',
        'longitude',
        'agora_uid',
        'fcm_token',
        'is_private',
        'is_frozen',
        'taggable',
        'commentable',
        'general_email_notification',
        'general_sms_notification',
        'general_push_notification',
        'like_notification',
        'comment_notification',
        'follower_notification',
        'taggable_notification',
        'collection_uuid',
        'is_banned',
        'banned_at',
        'ban_reason',
        'coin_balance',
        'earned_coin_balance',
        'country_id',
        'city_id',
        'age',
        'account_type',
        'parent_user_id',
        'deletion_requested',
        'deletion_requested_at',
        'deletion_reason',
        'is_approved',
        'approved_at',
        'approved_by',
        'last_seen_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => DatetimeTz::class,
            'updated_at' => DatetimeTz::class,
            'email_verified_at' => DatetimeTz::class,
            'phone_verified_at' => DatetimeTz::class,
            'birthday' => 'date',
            'password' => 'hashed',
            'general_push_notification' => 'boolean',
            'like_notification' => 'boolean',
            'comment_notification' => 'boolean',
            'follower_notification' => 'boolean',
            'taggable_notification' => 'boolean',
            'agora_uid' => 'integer',
            'privacy_settings' => 'json',
            'is_banned' => 'boolean',
            'banned_at' => DatetimeTz::class,
            'deletion_requested' => 'boolean',
            'deletion_requested_at' => DatetimeTz::class,
            'approved_at' => DatetimeTz::class,
            'last_seen_at' => DatetimeTz::class,
        ];
    }

    protected $appends = [
        'has_active_punishment',
    ];

    public function getAvatarAttribute($value)
    {
        if (empty($value)) {
            return null;
        }

        // Eğer zaten tam URL ise değiştirme
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        // Relative path ise BunnyCDN URL'i ile birleştir
        $cdnUrl = config('bunnycdn-storage.cdn_url', env('BUNNYCDN_STORAGE_CDN_URL'));
        $cleanPath = ltrim($value, '/');

        return rtrim($cdnUrl, '/') . '/' . $cleanPath;
    }

    public function coin_transactions(): HasMany
    {
        return $this->hasMany(UserCoinTransaction::class, 'user_id', 'id');
    }

    public function gift_baskets(): HasMany
    {
        return $this->hasMany(GiftBasket::class);
    }

    public function followers(): HasMany
    {
        return $this->hasMany(Follow::class, 'followed_id');
    }

    public function approvalLogs(): HasMany
    {
        return $this->hasMany(UserApprovalLog::class);
    }

    public function follows()
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'followed_id')
            ->withTimestamps()
            ->withTrashed();
    }

    public function coin_withdrawal_requests(): HasMany
    {
        return $this->hasMany(CoinWithdrawalRequest::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    public function getAgeAttribute(): int
    {
        return Carbon::parse($this->birthday)->age;
    }

    /**
     * Kullanıcının hesap silme isteklerini alır.
     */
    public function accountDeletionRequests(): HasMany
    {
        return $this->hasMany(AccountDeletionRequest::class);
    }

    /**
     * Kullanıcının aktif hesap silme isteği olup olmadığını kontrol eder.
     */
    public function hasActiveDeletionRequest(): bool
    {
        return $this->accountDeletionRequests()
            ->where('status', 'pending')
            ->exists();
    }

    public function primary_team()
    {
        return $this->belongsTo(Team::class, 'primary_team_id');
    }

    public function gender(): BelongsTo
    {
        return $this->belongsTo(Gender::class);
    }

    public function teams()
    {
        return $this->user_teams();
    }

    /**
     * Kullanıcının favori takımları
     *
     * @return array
     */
    public function favorite_teams()
    {
        // MongoDB'de saklanan favori takım ID'lerini döndür
        $favoriteTeams = $this->getAttribute('favorite_teams') ?? [];

        // Doğrudan array olarak döndür
        return is_array($favoriteTeams) ? $favoriteTeams : [];
    }

    public function user_stats(): HasOne
    {
        return $this->hasOne(UserStats::class);
    }

    public function deleteAvatar(): void
    {
        if (!$this->avatar) {
            return;
        }

        $path = str_replace('/storage/', '', parse_url($this->avatar, PHP_URL_PATH));

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    public function fullName(): Attribute
    {
        return Attribute::get(
            fn() => "{$this->name} {$this->surname}"
        );
    }

    /**
     * Ana kullanıcı ile ilişki
     */
    public function parentUser()
    {
        return $this->belongsTo(User::class, 'parent_user_id');
    }

    /**
     * İkincil hesaplar ile ilişki
     */
    public function secondaryAccounts()
    {
        return $this->hasMany(User::class, 'parent_user_id');
    }

    /**
     * Aynı email adresine sahip tüm hesapları getir
     */
    public function getLinkedAccounts()
    {
        // Eğer bu bir ana hesapsa, ikincil hesaplarını getir
        if ($this->account_type === 'primary') {
            return $this->secondaryAccounts;
        }

        // Eğer bu bir ikincil hesapsa, ana hesabı ve diğer ikincil hesapları getir
        if ($this->account_type === 'secondary' && $this->parent_user_id) {
            $parentUser = $this->parentUser;
            if ($parentUser) {
                $accounts = $parentUser->secondaryAccounts()
                    ->where('id', '!=', $this->id)
                    ->get();
                return $accounts->prepend($parentUser);
            }
        }

        return collect([]);
    }

    /**
     * Farklı email adreslerine sahip bağlantılı hesapları getir
     */
    public function getOtherEmailAccounts()
    {
        // Kullanıcının cihazlarından giriş yapılan diğer hesapları getir
        $deviceUniqueIds = $this->devices()->pluck('device_unique_id');

        if ($deviceUniqueIds->isEmpty()) {
            return collect([]);
        }

        // Aynı cihazlardan giriş yapan farklı email adresli kullanıcıları bul
        return User::whereHas('devices', function ($query) use ($deviceUniqueIds) {
            $query->whereIn('device_unique_id', $deviceUniqueIds);
        })
            ->where('id', '!=', $this->id)
            ->where(function ($query) {
                $query->where('email', '!=', $this->email)
                    ->orWhereNull('email');
            })
            ->get();
    }

    /**
     * Kullanıcının cihaz girişleri ile ilişki
     */
    public function deviceLogins()
    {
        return $this->hasMany(UserDeviceLogin::class);
    }

    /**
     * Kullanıcının cihazları ile ilişki
     */
    public function devices()
    {
        return $this->hasMany(UserDevice::class);
    }

    /**
     * Kullanıcının iki faktörlü kimlik doğrulama bilgisi ile ilişki
     */
    public function twoFactorAuth()
    {
        return $this->hasOne(TwoFactorAuth::class);
    }

    /**
     * Kullanıcının iki faktörlü kimlik doğrulama durumunu döndürür
     */
    public function getTwoFactorEnabledAttribute()
    {
        $twoFactorAuth = $this->twoFactorAuth;
        return $twoFactorAuth && $twoFactorAuth->verified;
    }

    public function getApprovedAt(): Attribute
    {
        return Attribute::get(
            fn() => $this->approved_at?->translatedFormat((new CommonHelper)->defaultDateTimeFormat(true))
        );
    }

    public function punishments(): HasMany
    {
        return $this->hasMany(UserPunishment::class);
    }

    public function getActivePunishment(): ?UserPunishment
    {
        return $this->punishments()
            ->where(function ($query) {
                $query->whereNotNull('expires_at')
                    ->orWhere('expires_at', '>', Carbon::now());
            })
            ->first();
    }

    public function hasActivePunishment(): Attribute
    {
        return Attribute::get(function () {
            $this->loadMissing('punishments');

            return $this->punishments()
                ->where(function ($query) {
                    $query->whereNotNull('expires_at')
                        ->orWhere('expires_at', '>', Carbon::now());
                })
                ->exists();
        });
    }

    public function activeStoriesCount(): Attribute
    {
        return Attribute::get(function () {
            return Story::where('user_id', $this->id)
                ->active()
                ->count();
        });
    }

    public function hasActiveStories(): Attribute
    {
        return Attribute::get(function () {
            return Story::where('user_id', $this->id)
                ->active()
                ->exists();
        });
    }

    public function hasActiveLiveStream(): Attribute
    {
        return Attribute::get(function () {
            return AgoraChannel::where('user_id', $this->id)
                ->where('status_id', AgoraChannel::STATUS_LIVE)
                ->exists();
        });
    }

    public function activeLiveStream(): Attribute
    {
        return Attribute::get(function () {
            return AgoraChannel::where('user_id', $this->id)
                ->where('status_id', AgoraChannel::STATUS_LIVE)
                ->first();
        });
    }

    public function isOnline(): Attribute
    {
        return Attribute::get(
            fn() => Redis::sismember('active-users', $this->id)
        );
    }

    public function getCreatedAt(): Attribute
    {
        return Attribute::get(
            fn() => $this->created_at?->translatedFormat((new CommonHelper)->defaultDateTimeFormat(true))
        );
    }

    public function allConversationsUserIds(): array
    {
        //$key = "user:all-conversations-user-ids:{$this->id}";

        // return Cache::remember($key, 1800, function () {
        $conversations = Conversation::where('participants', 'all', [$this->id])->latest()->limit(200)->get();
        $receiverIds = collect();
        foreach ($conversations as $conversation) {
            $otherParticipants = array_filter(
                $conversation->participants,
                fn($id) => $id !== $this->id
            );
            $receiverIds = $receiverIds->merge($otherParticipants);
        }
        $receiverIds = $receiverIds->unique();

        return $receiverIds->toArray();
        // });
    }
}
