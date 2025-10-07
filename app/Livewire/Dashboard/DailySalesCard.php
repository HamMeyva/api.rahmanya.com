<?php

namespace App\Livewire\Dashboard;

use Carbon\Carbon;
use Livewire\Component;
use App\Models\Morph\Payment;

class DailySalesCard extends Component
{
    public $isLoading = true, $totalAmount = 0;

    public function mount(): void {}

    public function loadData(): void
    {
        $result = $this->getDailySalesData();
        $this->totalAmount = $result['total_amount'];

        $weeklySalesChartData = $this->getDailySalesChartData();
        $this->dispatch('weeklySalesChartDataLoaded', ['data' => $weeklySalesChartData['data'], 'categories' => $weeklySalesChartData['categories']]);

        $this->isLoading = false;
    }


    public function render()
    {
        return view('livewire.dashboard.daily-sales-card');
    }

    public function getDailySalesData(): array
    {
        $today = now()->startOfDay();

        $totalAmount = Payment::query()
            ->where('status_id', Payment::STATUS_COMPLETED)
            ->where('created_at', '>=', $today)
            ->sum('total_amount');

        return [
            'total_amount' => $totalAmount
        ];
    }


    public function getDailySalesChartData(): array
    {
        $startDate = Carbon::now()->subDays(6)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        // Satış verilerini gün bazlı topla
        $rawData = Payment::query()
            ->selectRaw("DATE(created_at) as date, SUM(total_amount) as total")
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status_id', Payment::STATUS_COMPLETED)
            ->groupByRaw("DATE(created_at)")
            ->pluck('total', 'date');

        $dates = collect();
        for ($i = 0; $i < 7; $i++) {
            $date = Carbon::now()->subDays(6 - $i)->startOfDay();
            $dates->push($date);
        }

        $categories = [];
        $data = [];

        foreach ($dates as $date) {
            $dateKey = $date->toDateString();
            $categories[] = $date->translatedFormat('l');
            $data[] = (float) ($rawData[$dateKey] ?? 0);
        }

        return [
            'categories' => $categories,
            'data' => [
                [
                    'name' => 'Satışlar',
                    'data' => $data
                ]
            ],
        ];
    }
}
