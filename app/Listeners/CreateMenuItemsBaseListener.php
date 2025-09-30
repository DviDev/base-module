<?php

declare(strict_types=1);

namespace Modules\Base\Listeners;

use Modules\Permission\Enums\Actions;
use Modules\Permission\Models\PermissionActionModel;
use Modules\Person\Enums\UserType;
use Modules\Project\Contracts\CreateMenuItemsListenerContract;
use Modules\Project\Events\CreateMenuItemsEvent;
use Modules\Schema\Entities\ModuleMenuItem\ProjectModuleMenuItemEntityModel;

final class CreateMenuItemsBaseListener extends CreateMenuItemsListenerContract
{
    protected function moduleName(): string
    {
        return 'Base';
    }

    protected function createMenuItems(CreateMenuItemsEvent $event): void
    {
        $p = ProjectModuleMenuItemEntityModel::props();

        $event->menu->menuItems()->create([
            $p->label => ucfirst(__('config')).' (manual)',
            $p->title => ucfirst(__('config')).' (manual)',
            $p->num_order => 2,
            $p->url => route('admin.configs'),
            $p->active => true,
            $p->action_id => $this->getActionConfig()->id,
        ]);

        $event->menu->menuItems()->create([
            $p->label => 'Menu',
            $p->title => 'Menu',
            $p->num_order => 2,
            $p->url => route('admin.menu'),
            $p->active => true,
            $p->action_id => $this->getActionConfig()->id,
        ]);

        parent::createMenuItems($event);

        $event->menu->active = true;
        $event->menu->save();
    }

    protected function getActionConfig(): PermissionActionModel
    {
        $action = PermissionActionModel::create(['name' => Actions::view->name, 'title' => trans(Actions::view->name)]);
        $action->firstOrCreateGroup()
            ->createCondition(UserType::DEVELOPER)
            ->createCondition(UserType::SUPER_ADMIN)
            ->createCondition(UserType::ADMIN);

        return $action;
    }
}
