<?php

declare(strict_types=1);

namespace Modules\Base\Providers;

use Illuminate\Database\Eloquent\Model;
use Livewire\Livewire;
use Modules\Base\Console\DispatchBaseEventsCommand;
use Modules\Base\Console\DispatchInitialIndependentDataEventCommand;
use Modules\Base\Console\FeatureFlushCommand;
use Modules\Base\Console\InstallCommand;
use Modules\Base\Console\InstallModulesCommand;
use Modules\Base\Contracts\BaseServiceProviderContract;
use Modules\Base\Http\Middleware\UseSpotlightMiddleware;
use Modules\Base\Livewire\Config\ConfigList;
use Modules\Base\Livewire\Notification\NotificationList;
use Modules\Base\Livewire\Notification\NotificationView;
use Nwidart\Modules\Traits\PathNamespace;

final class BaseNewServiceProvider extends BaseServiceProviderContract
{
    use PathNamespace;

    public function boot(): void
    {
        parent::boot();

        Model::preventLazyLoading();
    }

    public function register(): void
    {
        parent::register();

        $this->registerGlobalWebMiddlewares();
    }

    protected function registerGlobalWebMiddlewares(): void
    {
        $router = $this->app['router'];
        if (config('base.use.spotlight')) {
            $router->pushMiddlewareToGroup('web', UseSpotlightMiddleware::class);
        }
    }

    public function provides(): array
    {
        return [
            EventServiceProvider::class,
            RouteServiceProvider::class,
            BaseEventServiceProvider::class,
        ];
    }

    public function getModuleName(): string
    {
        return 'Base';
    }

    public function getModuleNameLower(): string
    {
        return 'base';
    }

    protected function registerCommands(): void
    {
        $this->commands(FeatureFlushCommand::class);
        $this->commands(InstallModulesCommand::class);
        $this->commands(DispatchInitialIndependentDataEventCommand::class);
        $this->commands(DispatchBaseEventsCommand::class);
        $this->commands(InstallCommand::class);
    }

    protected function registerComponents(): void
    {
        Livewire::component('base::notification.notification-list', NotificationList::class);
        Livewire::component('base::notification.notification-view', NotificationView::class);
        Livewire::component('base::config.config-list', ConfigList::class);
    }
}
