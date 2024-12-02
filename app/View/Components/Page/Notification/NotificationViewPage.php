<?php

namespace Modules\Base\View\Components\Page\Notification;

use Illuminate\View\View;
use Modules\DvUi\View\BaseBladeComponent;

class NotificationViewPage extends BaseBladeComponent
{
    /**
     * Get the view/contents that represent the component.
     */
    public function render(): View|string
    {
        if ($this->published('page.notification.notification-view-page')) {
            return view('components.base.page.notification.notification-view-page');
        }
        return view('base::components.page.notification.notification-view-page');
    }
}
