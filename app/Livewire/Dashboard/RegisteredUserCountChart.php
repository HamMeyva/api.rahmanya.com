<?php

namespace App\Livewire\Dashboard;

use Carbon\Carbon;
use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Cache;

class RegisteredUserCountChart extends Component
{
    public $isLoading = true, $categories = [], $data = [];

    public function loadData(): void
    {
        $result = $this->getRegisteredUserCount();

        $this->categories = $result['categories'];
        $this->data = $result['data'];

        $this->dispatch('registeredUserCountDataLoaded', ['categories' => $result['categories'], 'data' => $result['data']]);

        $this->isLoading = false;
    }

    public function render()
    {
        return view('livewire.dashboard.registered-user-count-chart');
    }

    public function getRegisteredUserCount()
    {
        return Cache::remember('dashboard:registered-user-count-chart', 3600, function () {
            $categories = [];
            $todayData = [];
            $lastMonthData = [];
    
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i);
                $lastMonthDate = $date->copy()->subMonth();
    
                $categories[] = $date->translatedFormat('l');
    
                $todayCount = User::whereDate('created_at', $date)->count();
                $lastMonthCount = User::whereDate('created_at', $lastMonthDate)->count();
    
                $todayData[] = $todayCount;
                $lastMonthData[] = $lastMonthCount;
            }
    
            return [
                'categories' => $categories,
                'data' => [
                    [
                        'name' => "Bugün",
                        'data' => $todayData,
                    ],
                    [
                        'name' => "Geçen Ay Bugün",
                        'data' => $lastMonthData,
                    ],
                ],
            ];
        });
    }
}
