<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class Notifications extends Component
{
    protected $listeners = ['handleNotification' => 'handleNotification'];

    public $notifications;
    public $user;
    public $unreadCount;


    public function mount(): void
    {
        $this->user = Auth::user();
        $this->notifications = $this->user
            ->unreadNotifications()
            ->latest()
            ->limit(100)
            ->get()
            ->toArray();
            
        $this->unreadCount = $this->user->unreadNotifications->count();
    }

    public function handleNotification($id, $data): void
    {
        $this->unreadCount++;
        
        $data['id'] = $id;
        $data['created_at'] = Carbon::now();
        $data['read_at'] = null;

        array_unshift($this->notifications, $data);
    }

    public function markAsRead($notificationId)
    {
        $notification = $this->user->notifications()->find($notificationId);

        if ($notification) {
            $notification->markAsRead();
            $this->unreadCount--;
            $this->notifications = $this->user->notifications->toArray();
        }

        if (isset($notification['url'])) {
            return redirect()->to($notification['url']);
        }
    }


    public function render()
    {
        return view('livewire.notifications');
    }
}
