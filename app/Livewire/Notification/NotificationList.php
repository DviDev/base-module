<?php

declare(strict_types=1);

namespace Modules\Base\Livewire\Notification;

use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

final class NotificationList extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    protected $notifications;

    public function mount($notifications, $paginationTheme = 'bootstrap')
    {
        $this->notifications = $notifications;
        $this->paginationTheme = $paginationTheme;
    }

    public function render(): View
    {
        return view('base::livewire.notification.notification-list');
    }

    public function notifications()
    {
        return $this->notifications ?: auth()->user()->notifications()->paginate(15);
    }
}
