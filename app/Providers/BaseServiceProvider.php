<?php

namespace Modules\Base\Providers;

use Illuminate\Database\Eloquent\Model;
use Livewire\Livewire;
use Modules\Base\Console\DispatchBaseEventsCommand;
use Modules\Base\Console\DispatchInitialIndependentDataEventCommand;
use Modules\Base\Console\FeatureFlushCommand;
use Modules\Base\Console\InstallModulesCommand;
use Modules\Base\Http\Livewire\Config\ConfigForm;
use Modules\Base\Http\Livewire\Config\ConfigList;
use Modules\Base\Http\Livewire\Config\ConfigListItem;
use Modules\Base\Http\Livewire\Notification\NotificationList;
use Modules\Base\Http\Livewire\Notification\NotificationView;
use Modules\Base\Http\Middleware\UseSpotlightMiddleware;
use Modules\Base\Services\Errors\BaseTypeErrors;
use Modules\Base\View\Components\Page\Notification\NotificationListPage;
use Modules\Base\View\Components\Page\Notification\NotificationViewPage;

class BaseServiceProvider extends BaseServiceProviderContract
{
    protected string $moduleName = 'Base';

    protected string $moduleNameLower = 'base';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));

        $this->registerCommands();

        // real time check many queries ex. in post list using post->author suggest use post::with('user')->all()
        Model::preventLazyLoading(! $this->app->isProduction());

        //        Spotlight::registerCommandIf(config('base.use.spotlight'), GotoCommand::class);
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->app->register(BaseEventServiceProvider::class);

        $this->registerGlobalWebMiddlewares();

        $this->registerLivewireComponents();
    }

    protected function registerLivewireComponents(): void
    {
        self::publishableComponent('page.notification.notification-list-page', NotificationListPage::class);
        self::publishableComponent('page.notification.notification-view-page', NotificationViewPage::class);

        Livewire::component('base::config.config-form', ConfigForm::class);
        Livewire::component('base::config.config-list', ConfigList::class);
        Livewire::component('base::config.config-list-item', ConfigListItem::class);
        Livewire::component('base::notification.notification-list', NotificationList::class);
        Livewire::component('base::notification.notification-view', NotificationView::class);
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $config_module_path = module_path($this->moduleName, 'Config');
        $config_module_path_lower = module_path($this->moduleName, 'config');
        if (is_dir($config_module_path)) {
            rename($config_module_path, $config_module_path_lower);
        }
        $this->publishes([
            module_path($this->moduleName, 'config/config.php') => config_path($this->moduleNameLower.'.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/config.php'), $this->moduleNameLower
        );
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->moduleNameLower);

        $sourcePath = module_path($this->moduleName, 'resources/views');

        $this->publishes([
            $sourcePath => $viewPath,
        ], ['views', $this->moduleNameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->moduleNameLower);
        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, 'resources/lang'), $this->moduleNameLower);
            $this->loadJsonTranslationsFrom(module_path($this->moduleName, 'resources/lang'));
        }
    }

    /**
     * Register an additional directory of factories.
     *
     * @return void
     */
    public function registerFactories()
    {
        //        if (! app()->environment('production') && $this->app->runningInConsole()) {
        //            app(\Factory::class)->load(module_path($this->moduleName, 'Database/factories'));
        //        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    protected function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (\Config::get('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->moduleNameLower)) {
                $paths[] = $path.'/modules/'.$this->moduleNameLower;
            }
        }

        return $paths;
    }

    protected function registerCommands(): void
    {
        $this->commands(FeatureFlushCommand::class);
        $this->commands(InstallModulesCommand::class);
        $this->commands(DispatchInitialIndependentDataEventCommand::class);
        $this->commands(DispatchBaseEventsCommand::class);
    }

    public static function errorTypeClass()
    {
        return BaseTypeErrors::class;
    }

    protected function registerGlobalWebMiddlewares(): void
    {
        $router = $this->app['router'];
        if (config('base.use.spotlight')) {
            $router->pushMiddlewareToGroup('web', UseSpotlightMiddleware::class);
        }
    }

    public function getModuleNameLower(): string
    {
        return 'base';
    }

    public function getModuleName(): string
    {
        return 'Base';
    }
}
