<?php

namespace Modules\Base\Listeners;

use Modules\Base\Entities\Actions\Actions;
use Modules\Person\Entities\User\UserType;
use Modules\Project\Entities\MenuItem\MenuItemEntityModel;
use Modules\Project\Events\CreateMenuItemsEvent;
use Modules\Project\Listeners\CreateMenuItemsListenerContract;
use Modules\Project\Models\MenuModel;
use Modules\Project\Models\ProjectActionModel;
use Modules\Project\Models\ProjectModuleModel;

class CreateMenuItemsBaseListener extends CreateMenuItemsListenerContract
{
    public function handle(CreateMenuItemsEvent $event): void
    {
        if (MenuModel::query()->where('name', 'Admin')->exists()) {
            return;
        }

        $menu = MenuModel::firstOrCreate(
            ['name' => 'Admin', 'title' => 'Admin', 'num_order' => 1, 'active' => true]
        );
        $p = MenuItemEntityModel::props();

        $menu->menuItems()->create([
            $p->label => ucfirst(__('config')) . ' (manual)',
            $p->title => ucfirst(__('config')) . ' (manual)',
            $p->num_order => 2,
            $p->url => route('admin.configs'),
            $p->active => false,
            $p->action_id => $this->getActionConfig()->id,
        ]);

        $menu->menuItems()->create([
            $p->label => 'Menu',
            $p->title => 'Menu',
            $p->num_order => 2,
            $p->url => route('admin.menu'),
            $p->active => true,
            $p->action_id => $this->getActionConfig()->id,
        ]);

        parent::handle($event);
    }

    public function moduleName(): string
    {
        return 'Base';
    }

    protected function getActionConfig(): ProjectActionModel
    {
        $action = ProjectActionModel::create(['name' => Actions::view->name, 'title' => trans(Actions::view->name)]);
        $action->firstOrCreateGroup()
            ->createCondition(UserType::DEVELOPER)
            ->createCondition(UserType::SUPER_ADMIN)
            ->createCondition(UserType::ADMIN);

        return $action;
    }

    protected function createMenuItems(ProjectModuleModel $module, CreateMenuItemsEvent $event): void
    {
        parent::createMenuItems($module, $event);

        $event->menu->active = null;
        $event->menu->save();
    }
}
