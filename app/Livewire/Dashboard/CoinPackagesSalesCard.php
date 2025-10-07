<?php

namespace App\Livewire\Dashboard;

use App\Models\Morph\Payment;
use Livewire\Component;

class CoinPackagesSalesCard extends Component
{
    public $isLoading = true, $payments = [], $totalAmount = 0;

    public function mount(): void {}

    public function loadData(): void
    {
        $this->payments = $this->getCoinPackagesSalesData();
        $this->isLoading = false;
    }

    public function render()
    {
        return view('livewire.dashboard.coin-packages-sales-card');
    }

    public function getCoinPackagesSalesData()
    {
        return Payment::query()
            ->with(['user'])
            ->where('payable_type', "CoinPackage")
            ->where('status_id', Payment::STATUS_COMPLETED)
            ->latest()->limit(10)->get();
    }
}
