<?php

namespace Modules\Base\Providers;

use Illuminate\Database\Eloquent\Model;
use Livewire\Livewire;
use LivewireUI\Spotlight\Spotlight;
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
use Modules\Base\Spotlight\GotoCommand;
use Modules\Base\View\Components\Page\Notification\NotificationListPage;
use Modules\Base\View\Components\Page\Notification\NotificationViewPage;

class BaseServiceProvider extends BaseServiceProviderContract
{
    /**
     * @var string $moduleName
     */
    protected string $moduleName = 'Base';

    /**
     * @var string $moduleNameLower
     */
    protected string $moduleNameLower = 'base';

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));

        $this->registerCommands();

        //real time check many queries ex. in post list using post->author suggest use post::with('user')->all()
        Model::preventLazyLoading(!$this->app->isProduction());

        Spotlight::registerCommandIf(config('base.use.spotlight'), GotoCommand::class);
    }

    /**
     * Register the service provider.
     *
     * @return void
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
     *
     * @return void
     */
    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower . '.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'), $this->moduleNameLower
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/' . $this->moduleNameLower);

        $sourcePath = module_path($this->moduleName, 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath
        ], ['views', $this->moduleNameLower . '-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/' . $this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, 'Resources/lang'), $this->moduleNameLower);
            $this->loadJsonTranslationsFrom(module_path($this->moduleName, 'Resources/lang'));
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
     *
     * @return array
     */
    public function provides(): array
    {
        return [];
    }

    protected function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (\Config::get('view.paths') as $path) {
            if (is_dir($path . '/modules/' . $this->moduleNameLower)) {
                $paths[] = $path . '/modules/' . $this->moduleNameLower;
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
