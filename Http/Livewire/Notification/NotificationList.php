<?php

namespace Modules\Base\Http\Livewire\Notification;

use Livewire\Component;
use Livewire\WithPagination;

class NotificationList extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public function render()
    {
        return view('base::livewire.notification.notification-list');
    }

    public function notifications()
    {
        return auth()->user()->notifications()->paginate(15);
    }
}
