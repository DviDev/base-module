<?php

declare(strict_types=1);

namespace Modules\Base\View\Components\Page\Notification;

use Illuminate\View\View;
use Modules\DvUi\Enums\DvuiComponentAlias;
use Modules\DvUi\View\BaseBladeComponent;

final class NotificationListPage extends BaseBladeComponent
{
    /**
     * Get the view/contents that represent the component.
     */
    public function render(): View|string
    {
        if ($this->published('page.notification.notificationlistpage')) {
            return view('components.base.page.notification.notification-list-page');
        }

        return view('base::components.page.notification.notification-list-page');
    }

    public function componentAlias(): DvuiComponentAlias
    {
        return DvuiComponentAlias::NotificationListPage;
    }
}
