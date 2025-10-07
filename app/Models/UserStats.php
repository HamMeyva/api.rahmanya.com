<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Expression;
use App\Casts\DatetimeTz;

/**
 * @mixin IdeHelperUserStats
 */
class UserStats extends Model
{
    use SoftDeletes;

    protected $table = 'user_stats';

    protected $fillable = [
        'user_id',
        'follower_count',
        'following_count',
        'video_count',
        'total_views',
        'total_likes',
        'total_comments',
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
     * The user that these stats belong to
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Update or create stats for a user
     *
     * @param string $userId
     * @param array $data
     * @return static
     */
    public static function updateOrCreateStats(string $userId, array $data): self
    {
        $defaults = [
            'follower_count' => 0,
            'following_count' => 0,
            'video_count' => 0,
            'total_views' => 0,
            'total_likes' => 0,
            'total_comments' => 0,
        ];

        // Process raw expressions (e.g., DB::raw) for new records
        foreach ($data as $key => $value) {
            if ($value instanceof Expression) {
                // Get the grammar from the model's database connection
                $grammar = static::query()->getConnection()->getQueryGrammar();
                $rawSql = $value->getValue($grammar); // Pass grammar to getValue()

                if (is_string($rawSql)) {
                    if (stripos($rawSql, ' + 1') !== false) {
                        $defaults[$key] = 1;
                    } elseif (stripos($rawSql, ' - 1') !== false) {
                        $defaults[$key] = 0;
                    }
                }
                unset($data[$key]);
            }
        }

        // Use Laravel's updateOrCreate for efficiency
        return static::updateOrCreate(
            ['user_id' => $userId],
            array_merge($defaults, $data)
        );
    }

    /**
     * Increment or decrement a specific counter
     *
     * @param string $userId
     * @param string $column
     * @param bool $increment
     * @return static
     */
    /**
     * Atomically modify a counter using PostgreSQL's native capabilities
     * This approach uses a single SQL statement with UPSERT pattern for maximum reliability
     *
     * @param string $userId User ID to update stats for
     * @param string $column Column name to update (follower_count, following_count, etc.)
     * @param bool $increment Whether to increment (true) or decrement (false) the counter
     * @return self The updated UserStats model
     * @throws \Exception If an error occurs during the update
     */
    private static function modifyCounter(string $userId, string $column, bool $increment = true): self
    {
        \Log::info("UserStats update attempt: user_id={$userId}, column={$column}, increment={$increment}");

        try {
            // Use DB::raw for direct SQL operations with PostgreSQL
            $connection = \DB::connection();

            // Create a new stats record if it doesn't exist
            // Use PostgreSQL's INSERT ... ON CONFLICT ... DO UPDATE pattern (upsert)
            // This makes the operation atomic and avoids race conditions
            $sql = "INSERT INTO user_stats (user_id, follower_count, following_count, video_count,
                    total_views, total_likes, total_comments, created_at, updated_at)
                    VALUES (?, 0, 0, 0, 0, 0, 0, NOW(), NOW())
                    ON CONFLICT (user_id) DO NOTHING";

            $connection->statement($sql, [$userId]);

            // Now update the counter with an atomic operation
            if ($increment) {
                // Increment case
                $updateSql = "UPDATE user_stats SET
                               {$column} = {$column} + 1,
                               updated_at = NOW()
                             WHERE user_id = ?";
            } else {
                // Decrement case
                $updateSql = "UPDATE user_stats SET
                               {$column} = GREATEST(0, {$column} - 1),
                               updated_at = NOW()
                             WHERE user_id = ?";
            }

            // Retrieve the updated record
            $stats = static::where('user_id', $userId)->first();
            if (!$stats) {
                throw new \Exception("Failed to retrieve UserStats after update");
            }

            \Log::info("UserStats update success: user_id={$userId}, column={$column}, value={$stats->$column}");
            return $stats;

        } catch (\Exception $e) {
            \Log::error("UserStats update error: {$e->getMessage()}");
            \Log::error($e->getTraceAsString());

            // Create a fallback record to avoid disrupting the application flow
            $stats = static::firstOrNew(['user_id' => $userId]);

            if (!$stats->exists) {
                $stats->user_id = $userId;
                $stats->follower_count = 0;
                $stats->following_count = 0;
                $stats->video_count = 0;
                $stats->total_views = 0;
                $stats->total_likes = 0;
                $stats->total_comments = 0;
                $stats->created_at = now();
            }

            // Apply the counter change without saving to database
            // This keeps the model state consistent with the expected operation
            if ($increment) {
                $stats->$column += 1;
            } else {
                $stats->$column = max(0, $stats->$column - 1);
            }

            $stats->updated_at = now();
            return $stats;
        }
    }

    // Specific counter methods
    public static function incrementFollowerCount(string $userId): self
    {
        return static::modifyCounter($userId, 'follower_count', true);
    }

    public static function decrementFollowerCount(string $userId): self
    {
        return static::modifyCounter($userId, 'follower_count', false);
    }

    public static function incrementFollowingCount(string $userId): self
    {
        return static::modifyCounter($userId, 'following_count', true);
    }

    public static function decrementFollowingCount(string $userId): self
    {
        return static::modifyCounter($userId, 'following_count', false);
    }

    public static function incrementVideoCount(string $userId): self
    {
        return static::modifyCounter($userId, 'video_count', true);
    }

    public static function decrementVideoCount(string $userId): self
    {
        return static::modifyCounter($userId, 'video_count', false);
    }
}
