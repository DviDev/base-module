<?php

namespace Modules\Base\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Modules\Flowbite\App\Providers\RouteServiceProvider;

abstract class BaseServiceProviderContract extends ServiceProvider
{
    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->getModuleName(), 'Database/migrations'));
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        // $this->commands([]);
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
            $this->loadTranslationsFrom(module_path($this->getModuleName(), 'lang'), $this->getModuleNameLower());
            $this->loadJsonTranslationsFrom(module_path($this->getModuleName(), 'lang'));
        }
    }

    abstract public function getModuleNameLower(): string;

    abstract public function getModuleName(): string;

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
        $viewPath = resource_path('views/components/' . $this->getModuleNameLower());
        $sourcePath = module_path($this->getModuleName(), 'Resources/views');
        $resourceComponentPath = module_path($this->getModuleName(), 'Resources/components');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->getModuleNameLower() . '-module-views']);

        $paths = array_merge($this->getPublishableViewPaths(), [
            $sourcePath,
            $resourceComponentPath
        ]);
        $this->loadViewsFrom(
            $paths,
            $this->getModuleNameLower()
        );

        $config = config('modules.paths.generator.component-class.path');
        $componentNamespace = str_replace('/', '\\', config('modules.namespace') . '\\' . $this->getModuleName() . '\\App\\View\\Components\\');
        Blade::componentNamespace($componentNamespace, $this->getModuleNameLower());

        $this->registerComponents();
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
            RouteServiceProvider::class
        ];
    }

    protected function publishableComponent($name, $class): void
    {
        $component = str($name)->explode('.')->join('/');
        $filename = resource_path('views/components/' . $this->getModuleNameLower() . '/' . $component);
        $namespace = $this->getModuleNameLower() . "::$name";
        if (File::exists($filename . '.blade.php')) {
            $path = 'components.' . $this->getModuleNameLower() . '.' . $name;
            Blade::component($path, $namespace);
        } else {
            Blade::component($namespace, $class);
        }

        list($origin, $destination) = $this->originAndDestination($name);

        $this->publishes(
            [$origin => $destination],
            [
                'views',
                'publishable-components',
                $this->getModuleNameLower() . "-publishable-components",
                $this->getModuleNameLower() . "-component-" . $name,
            ]
        );
    }

    protected function originAndDestination($name): array
    {
        $component = str($name)->explode('.')->join('/');
        $origin = module_path($this->getModuleName(), "Resources/views/components/$component.blade.php");
        $destination = resource_path("views/components/{$this->getModuleNameLower()}/$component.blade.php");
        return [$origin, $destination];
    }
}
