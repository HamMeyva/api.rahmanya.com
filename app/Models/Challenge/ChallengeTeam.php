<?php

namespace App\Models\Challenge;

use Mongodb\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use App\Observers\Challenge\ChallengeTeamObserver;
use App\Casts\DatetimeTz;
use App\Models\Traits\MongoTimestamps;

#[ObservedBy(ChallengeTeamObserver::class)]
/**
 * @mixin IdeHelperChallengeTeam
 */
class ChallengeTeam extends Model
{
    use MongoTimestamps;
    
    protected $connection = 'mongodb';
    protected $collection = 'challenge_teams';

    protected $fillable = [
        'challenge_id',
        'team_no', // 1 veya 2
        'user_id',
        'user_data',
        'total_coins_earned',
        'win_count', // Kazandığı win sayısı (her max_coins aşıldıgında bir win)
    ];

    protected function casts(): array
    {
        return [
            
        ];
    }
    /* start::Relations */
    public function challenge()
    {
        return $this->belongsTo(Challenge::class, 'challenge_id', '_id');
    }
    /* end::Relations */
}
