<?php

namespace Modules\Base\Http\Livewire\Notification;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;
use Livewire\Component;
use Modules\Base\Services\Notification\Action;

class NotificationView extends Component
{
    public DatabaseNotification $notification;

    public ?User $user;

    public function mount(): void
    {
        $this->user = $this->getUser();
        $this->notification->markAsRead();
    }

    /**
     * @return User
     */
    protected function getUser(): ?Model
    {
        return User::query()->find($this->notification->data['created_by_user_id'] ?? null);
    }

    public function render(): View
    {
        return view('base::livewire.notification.notification-view');
    }

    public function getAction(): ?Action
    {
        return new Action(
            $this->notification->data['action']['text'] ?? null,
            $this->notification->data['action']['url'] ?? null,
            $this->notification->data['action']['type'] ?? null,
            $this->notification->data['action']['btn'] ?? false,
            $this->notification->data['action']['icon'] ?? null,
        );
    }
}
