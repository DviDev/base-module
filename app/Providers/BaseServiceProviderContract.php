<?php

namespace Modules\Base\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\Flowbite\Providers\RouteServiceProvider;

abstract class BaseServiceProviderContract extends ServiceProvider
{
    use PublishableComponents;

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->configureCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerComponents();
        $this->loadMigrationsFrom(module_path($this->getModuleName(), 'Database/migrations'));
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        // $this->commands([]);
    }

    private function configureCommands(): void
    {
        \DB::prohibitDestructiveCommands(
            $this->app->isProduction()
        );
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        // $this->app->booted(function () {
        //     $schedule = $this->app->make(Schedule::class);
        //     $schedule->command('inspire')->hourly();
        // });
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/' . $this->getModuleNameLower());

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->getModuleNameLower());
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom($this->langPath(), $this->getModuleNameLower());
            $this->loadJsonTranslationsFrom($this->langPath());
        }
    }

    protected function langPath(): string
    {
        return module_path($this->getModuleName(), 'lang');
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $this->publishes([module_path($this->getModuleName(), 'config/config.php') => config_path($this->getModuleNameLower() . '.php')], 'config');
        $this->mergeConfigFrom(module_path($this->getModuleName(), 'config/config.php'), $this->getModuleNameLower());
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/' . $this->getModuleNameLower());
        $sourcePath = module_path($this->getModuleName(), 'Resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->getModuleNameLower() . '-module-views']);

        $path = array_merge(
            // Primeiro tenta carregar as views publicadas
            [$viewPath],
            // Depois tenta carregar as views do módulo
            [$sourcePath],
        );
        $this->loadViewsFrom(
            $path,
            $this->getModuleNameLower()
        );

        Blade::componentNamespace(config('modules.namespace') . '\\' . $this->getModuleName() . '\\View\\Components', $this->getModuleNameLower());
    }

    protected function registerComponents(): void
    {
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        foreach ($this->providers() as $provider) {
            $this->app->register($provider);
        }
    }

    public function providers(): array
    {
        return [
            RouteServiceProvider::class,
        ];
    }

    protected function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path . '/modules/' . $this->getModuleNameLower())) {
                $paths[] = $path . '/modules/' . $this->getModuleNameLower();
            }
        }

        return $paths;
    }
}
