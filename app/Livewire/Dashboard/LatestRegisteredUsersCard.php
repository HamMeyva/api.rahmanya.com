<?php

namespace App\Livewire\Dashboard;

use App\Models\User;
use Livewire\Component;

class LatestRegisteredUsersCard extends Component
{
    public $isLoading = true, $users = [];
    
    public function loadData()
    {
        $this->users = User::latest()->take(10)->get();
        $this->isLoading = false;
    }

    public function render()
    {
        return view('livewire.dashboard.latest-registered-users-card');
    }
}
