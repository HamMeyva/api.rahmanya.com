<?php

namespace App\Livewire\Dashboard;

use Carbon\Carbon;
use Livewire\Component;
use App\Models\Morph\Payment;

class CoinSalesChartCard extends Component
{
    public $isLoading = true, $categories = [],
        $data = [],
        $totalAmount = 0;

    public function mount(): void {}

    public function loadData(): void
    {
        $result = $this->getLast30DaysCoinSales();

        $this->categories = $result['categories'];
        $this->data = $result['data'];
        $this->totalAmount = $result['total_amount'];

        $this->dispatch('coinSalesDataLoaded', ['categories' => $result['categories'], 'data' => $result['data'], 'total_amount' => $result['total_amount']]);

        $this->isLoading = false;
    }


    public function render()
    {
        return view('livewire.dashboard.coin-sales-chart-card');
    }

    public function getLast30DaysCoinSales()
    {
        $sales = Payment::query()
            ->where('payable_type', 'CoinPackage')
            ->where('status_id', Payment::STATUS_COMPLETED)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw("DATE(created_at) as date, SUM(total_amount) as total")
            ->groupByRaw("DATE(created_at)")
            ->orderByRaw("DATE(created_at)")
            ->get();

        $startDate = now()->subDays(29)->startOfDay();
        $dates = collect();

        for ($i = 0; $i < 30; $i++) {
            $date = $startDate->copy()->addDays($i);
            $dates->put($date->format('Y-m-d'), 0);
        }

        foreach ($sales as $sale) {
            $dateKey = Carbon::parse($sale->date)->format('Y-m-d');
            $dates[$dateKey] = $sale->total;
        }

        $categories = $dates->keys()->map(function ($date) {
            return Carbon::parse($date)->format('d/m');
        });

        $data = $dates->values();

        $totalAmount = $sales->sum('total');

        return [
            'categories' => $categories->toArray(),
            'data' => $data->toArray(),
            'total_amount' => $totalAmount
        ];
    }
}
