<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Casts\DatetimeTz;

/**
 * @mixin IdeHelperBlock
 */
class Block extends Model
{
    use SoftDeletes;

    protected $table = 'user_blocks';

    protected $fillable = [
        'blocker_id',
        'blocked_id',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => DatetimeTz::class,
            'updated_at' => DatetimeTz::class,
            'deleted_at' => DatetimeTz::class,
        ];
    }

    /**
     * Engelleyen kullanıcı
     */
    public function blocker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocker_id');
    }

    /**
     * Engellenen kullanıcı
     */
    public function blocked(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_id');
    }

    public static function getBlockedUsers(String $userId): Builder
    {
        return static::query()->where('blocker_id', $userId);
    }

    public static function isBlocking(String $blockerId, String $blockedId): bool
    {
        return static::query()
            ->where('blocker_id', $blockerId)
            ->where('blocked_id', $blockedId)
            ->exists();
    }
}
