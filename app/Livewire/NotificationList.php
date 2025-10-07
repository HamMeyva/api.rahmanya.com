<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

class NotificationList extends Component
{
    use WithPagination;

    public $notifications;
    public int $page = 1;
    public int $perPage = 10;
    public bool $hasMorePages = true;


    public function mount()
    {
        $this->notifications = collect(); 
        $this->loadMore();
    }


    public function render()
    {
        return view('livewire.notification-list');
    }

    public function loadMore()
    {
        $notifications = Auth::user()
            ->notifications()
            ->latest()
            ->forPage($this->page, $this->perPage)
            ->get();

        $this->notifications = $this->notifications->concat($notifications);

        $this->page++;

        if ($notifications->count() < $this->perPage) {
            $this->hasMorePages = false;
        }
    }

    public function markAsRead($notificationId)
    {
        $now = now();

        Auth::user()
            ->notifications()
            ->where('id', $notificationId)
            ->update(['read_at' => $now]);

        $this->notifications = $this->notifications->map(function ($notification) use ($notificationId, $now) {
            if ($notification['id'] == $notificationId) {
                $notification['read_at'] = $now;
            }
            return $notification;
        });
    }
}
