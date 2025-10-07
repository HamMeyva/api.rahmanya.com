<?php

namespace App\Models\Agora;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Casts\DatetimeTz;

/**
 * @mixin IdeHelperAgoraStreamStatistic
 */
class AgoraStreamStatistic extends Model
{
    protected $table = 'agora_stream_statistics';

    protected $connection = 'pgsql';

    protected $fillable = [
        'agora_channel_id',
        'user_id',
        'date',
        'total_stream_duration',
        'total_viewers',
        'unique_viewers',
        'max_concurrent_viewers',
        'avg_watch_time',
        'total_comments',
        'total_likes',
        'total_gifts',
        'total_coins_earned',
        'new_followers_gained'
    ];

    protected function casts(): array
    {
        return [
            'created_at' => DatetimeTz::class,
            'updated_at' => DatetimeTz::class,
            'date' => 'date',
            'total_stream_duration' => 'integer',
            'total_viewers' => 'integer',
            'unique_viewers' => 'integer',
            'max_concurrent_viewers' => 'integer',
            'avg_watch_time' => 'integer',
            'total_comments' => 'integer',
            'total_likes' => 'integer',
            'total_gifts' => 'integer',
            'total_coins_earned' => 'integer',
            'new_followers_gained' => 'integer',
        ];
    }

    public function agoraChannel(): BelongsTo
    {
        return $this->belongsTo(AgoraChannel::class, 'agora_channel_id');
    }

    /**
     * Yayıncı kullanıcıyı getir
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Formatlanmış toplam yayın süresini getir
     * 
     * @return string
     */
    public function getFormattedStreamDurationAttribute(): string
    {
        $duration = $this->total_stream_duration ?? 0;

        $hours = floor($duration / 3600);
        $minutes = floor(($duration % 3600) / 60);
        $seconds = $duration % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    /**
     * Kullanıcıya göre istatistikleri filtreler
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Belirli bir tarih aralığına göre istatistikleri filtreler
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * En yüksek izleyici sayısına göre sıralar
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTopViewers($query)
    {
        return $query->orderBy('max_concurrent_viewers', 'desc');
    }

    /**
     * En yüksek kazanca göre sıralar
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTopEarners($query)
    {
        return $query->orderBy('total_coins_earned', 'desc');
    }
}
