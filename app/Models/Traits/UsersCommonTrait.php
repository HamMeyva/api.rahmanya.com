<?php

namespace App\Models\Traits;

use App\Models\Agora\AgoraChannel;
use App\Models\Relations\Team;
use App\Models\Relations\UserDevice;
use App\Models\Relations\VisitHistory;
use App\Models\UserDeviceLogin;
use App\Models\Video;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

enum FollowerPrivacy: int
{
    case FOLLOWING = 1;
    case MUTUAL_FOLLOWERS = 2;
    case NOBODY = 3;

    public function label(): string
    {
        return match ($this) {
            self::FOLLOWING => 'Followings',
            self::MUTUAL_FOLLOWERS => 'Mutual Followers',
            self::NOBODY => 'Nobody',
        };
    }
}

trait UsersCommonTrait
{
    public function primary_team(): HasOne
    {
        return $this->hasOne(Team::class, 'id', 'primary_team_id');
    }

    public function user_teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'user_team', 'user_id', 'team_id');
    }

    public function agora_channel(): HasOne
    {
        return $this->hasOne(AgoraChannel::class, 'user_id', 'id');
    }

    public function following(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'follows', 'follower_id', 'followed_id')
            ->withPivot(['status', 'notify_on_accept'])
            ->withTimestamps()
            ->whereRaw('follows.deleted_at IS NULL');
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'follows', 'followed_id', 'follower_id')
            ->withPivot(['status', 'notify_on_accept'])
            ->withTimestamps()
            ->whereRaw('follows.deleted_at IS NULL');
    }

    public function approvedFollowers(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'follows', 'followed_id', 'follower_id')
            ->withPivot(['status', 'notify_on_accept'])
            ->withTimestamps()
            ->whereRaw('follows.deleted_at IS NULL')
            ->wherePivot('status', 'approved');
    }

    public function pendingFollowRequests(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'follows', 'followed_id', 'follower_id')
            ->withPivot(['status', 'notify_on_accept'])
            ->withTimestamps()
            ->whereRaw('follows.deleted_at IS NULL')
            ->wherePivot('status', 'pending');
    }

    public function approvedFollowing(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'follows', 'follower_id', 'followed_id')
            ->withPivot(['status', 'notify_on_accept'])
            ->withTimestamps()
            ->whereRaw('follows.deleted_at IS NULL')
            ->wherePivot('status', 'approved');
    }

    public function pendingFollowingRequests(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'follows', 'follower_id', 'followed_id')
            ->withPivot(['status', 'notify_on_accept'])
            ->withTimestamps()
            ->whereRaw('follows.deleted_at IS NULL')
            ->wherePivot('status', 'pending');
    }

    public function blocked_users(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'user_blocks', 'blocker_id', 'blocked_id');
    }

    public function taggable_users(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'user_taggable', 'taggable_id', 'user_id')
            ->withPivot(['status', 'notify_on_tag', 'visibility'])
            ->withTimestamps();
    }

    public function users_i_can_tag(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'user_taggable', 'user_id', 'taggable_id')
            ->withPivot(['status', 'notify_on_tag', 'visibility'])
            ->withTimestamps();
    }

    public function commentable_users(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'user_commentable', 'commentable_id', 'user_id')
            ->withPivot(['status'])
            ->withTimestamps();
    }

    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class);
    }

    /**
     * Get all device login history for the user.
     */
    public function deviceLogins(): HasMany
    {
        return $this->hasMany(UserDeviceLogin::class)->orderBy('created_at', 'desc');
    }

    public function visit_histories(): HasMany
    {
        return $this->hasMany(VisitHistory::class);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    // MUTATORS

    public function isFollowedByMe($userId = null): bool
    {
        if ($userId === null) {
            $userId = Auth::id();
        }
        return $userId && $this->approvedFollowers()
            ->where('follows.follower_id', $userId)
            ->exists();
    }

    public function isFollowing($userId = null): bool
    {
        if ($userId === null) {
            $userId = Auth::id();
        }
        return $userId && $this->approvedFollowing()
            ->where('follows.followed_id', $userId)
            ->exists();
    }

    public function hasPendingFollowRequestFrom($userId = null): bool
    {
        if ($userId === null) {
            $userId = Auth::id();
        }
        return $userId && $this->pendingFollowRequests()
            ->where('follows.follower_id', $userId)
            ->exists();
    }

    public function getFollowStatusForUser($userId = null): string
    {
        if (null === $userId) {
            $userId = Auth::id();
            if (null === $userId) {
                return 'not_following';
            }
        }

        $follow = $this->followers()
            ->where('follows.follower_id', $userId)
            ->whereNull('follows.deleted_at')
            ->first();
            
        // Check if $follow exists and has a pivot property before accessing status
        if ($follow && isset($follow->pivot) && $follow->pivot) {
            return $follow->pivot->status;
        }
        
        return 'not_following';
    }

    public function canBeViewedBy($userId = null): bool
    {
        if (!$this->is_private) {
            return true;
        }
        
        if (null === $userId) {
            $userId = Auth::id();
            if (null === $userId) {
                return true; // If no user is authenticated, assume public access
            }
        }
        
        return $this->approvedFollowers()->where('follower_id', $userId)->exists();
    }

    public function getPrivacySettings()
    {
        $defaults = [
            'profile_visibility' => $this->is_private ? 'private' : 'public',
            'show_following' => true,
            'show_followers' => true,
            'allow_tagging' => true,
            'allow_comments' => true,
            'comment_privacy' => 'everyone',
            'tag_privacy' => 'everyone',
        ];
        
        // If privacy_settings is a string, try to decode it
        $settings = $this->privacy_settings;
        if (is_string($settings) && !empty($settings)) {
            $decoded = json_decode($settings, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $settings = $decoded;
            }
        }
        
        return array_merge($defaults, $settings ?? []);
    }

    public function updatePrivacySettings(array $settings): void
    {
        $validator = Validator::make($settings, [
            'profile_visibility' => 'in:public,private',
            'show_following' => 'boolean',
            'show_followers' => 'boolean',
            'allow_tagging' => 'boolean',
            'allow_comments' => 'boolean',
            'comment_privacy' => 'in:everyone,followers,mutual_followers,nobody',
            'tag_privacy' => 'in:everyone,followers,mutual_followers,nobody',
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException('Invalid privacy settings provided.');
        }

        $this->privacy_settings = array_merge($this->getPrivacySettings(), $validator->validated());
        $this->save();
    }

    public function togglePrivateAccount(): void
    {
        $this->is_private = !$this->is_private;
        $this->save();
    }

    public function isTaggableByMe($userId = null): bool
    {
        return $userId && $this->taggable_users()->where('user_id', $userId)->where('status', 'approved')->exists();
    }

    public function isCommentableByMe($userId = null): bool
    {
        return $userId && $this->commentable_users()->where('user_id', $userId)->where('status', 'approved')->exists();
    }

    /**
     * Get all approved comments on this user's content
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getApprovedComments()
    {
        return $this->commentable_users()
            ->where('status', 'approved')
            ->whereNull('parent_id')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get replies to a specific comment
     * @param int $commentId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getCommentReplies($commentId)
    {
        return $this->commentable_users()
            ->where('status', 'approved')
            ->where('parent_id', $commentId)
            ->orderBy('created_at', 'asc');
    }

    public function isBlockedByMe($userId = null): bool
    {
        return $userId && $this->blocked_users()->where('blocked_id', $userId)->exists();
    }

    public function isBlockedBy($userId = null): bool
    {
        return $userId && $this->blocked_users()->where('blocker_id', $userId)->exists();
    }

    public function FullName(): Attribute
    {
        return Attribute::get(fn () => trim($this->name . ' ' . $this->surname));
    }

    public function getAvatar(): Attribute
    {
        return Attribute::get(function () {
            if (!empty($this->avatar)) {
                return Storage::disk('public')->url($this->avatar);
            }
            $name = urlencode($this->name . ($this->surname ? '+' . $this->surname : ''));
            return 'https://ui-avatars.com/api/?name=' . $name . '&background=f59f00&color=f1f1f1&size=100';
        });
    }
}