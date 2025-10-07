<?php

namespace App\Livewire\Dashboard;

use App\Models\User;
use Livewire\Component;
use MongoDB\BSON\UTCDateTime;
use App\Models\Agora\AgoraChannel;

class DailyLiveStreamsCard extends Component
{
    public $isLoading = true, $count = 0, $duration = 0, $user_count = 0, $featured_users = [];

    public function mount(): void {}

    public function loadData(): void
    {
        $result = $this->getDailyVideosData();

        $this->count = $result['count'];
        $this->duration = $result['duration'];
        $this->user_count = $result['user_count'];
        $this->featured_users = $result['featured_users'];

        $this->isLoading = false;
    }

    public function render()
    {
        return view('livewire.dashboard.daily-live-streams-card');
    }

    public function getDailyVideosData()
    {
        $today = now()->startOfDay();
        $todayUtc = new UTCDateTime($today);

        $result = AgoraChannel::raw(function ($collection) use ($todayUtc) {
            return $collection->aggregate([
                [
                    '$match' => [
                        'started_at' => ['$gte' => $todayUtc]
                    ]
                ],
                [
                    '$group' => [
                        '_id' => null,
                        'count' => ['$sum' => 1],
                        'duration' => ['$sum' => '$duration'],
                        'user_ids' => ['$addToSet' => '$user_id'],
                    ]
                ],
                [
                    '$project' => [
                        'count' => 1,
                        'duration' => 1,
                        'user_count' => ['$size' => '$user_ids']
                    ]
                ]
            ]);
        });

        $data = $result->first();

        $featuredUsersResult = AgoraChannel::raw(function ($collection) use ($todayUtc) {
            return $collection->aggregate([
                [
                    '$match' => [
                        'created_at' => ['$gte' => $todayUtc]
                    ]
                ],
                [
                    '$group' => [
                        '_id' => '$user_id',
                        'count' => ['$sum' => 1]
                    ]
                ],
                [
                    '$sort' => ['count' => -1]
                ],
                [
                    '$limit' => 3
                ],
                [
                    '$project' => [
                        '_id' => 1
                    ]
                ]
            ]);
        });
        $featuredUsersResult = AgoraChannel::raw(function ($collection) use ($todayUtc) {
            return $collection->aggregate([
                [
                    '$match' => [
                        'started_at' => ['$gte' => $todayUtc],
                        'user_id' => ['$ne' => null]
                    ]
                ],
                [
                    '$group' => [
                        '_id' => '$user_id',
                        'count' => ['$sum' => 1]
                    ]
                ],
                [
                    '$sort' => ['count' => -1]
                ],
                [
                    '$limit' => 3
                ],
                [
                    '$project' => [
                        '_id' => 1
                    ]
                ]
            ]);
        });

        $featuredUserIds = collect($featuredUsersResult)->pluck('_id')->toArray();
        $featuredUsers = User::select(['id', 'name', 'surname', 'avatar'])->whereIn('id', $featuredUserIds)->get();

        $userCount = $data['user_count'] ?? 0;
        return [
            'count' => $data['count'] ?? 0,
            'duration' => $data['duration'] ?? 0,
            'user_count' => $userCount > 3 ? $userCount - 3 : 0,
            'featured_users' => $featuredUsers ?? []
        ];
    }
}
