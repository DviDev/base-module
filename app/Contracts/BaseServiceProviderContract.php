<?php

declare(strict_types=1);

namespace Modules\Base\Contracts;

use DB;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Base\Traits\PublishableComponents;
use Nwidart\Modules\Facades\Module;

abstract class BaseServiceProviderContract extends ServiceProvider
{
    use PublishableComponents;

    /**
     * Guarda por processo para evitar reentrância/duplicação de bootstrap por módulo.
     */
    protected static array $bootstrapped = [];

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

        $this->loadMigrationsFrom(module_path($this->getModuleName(), 'database/Migrations'));

        $this->registerRequiredModules();
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->getModuleNameLower());

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->getModuleNameLower());
            $this->loadJsonTranslationsFrom($langPath);

            return;
        }
        $this->loadTranslationsFrom($this->langPath(), $this->getModuleNameLower());
        $this->loadJsonTranslationsFrom($this->langPath());
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->getModuleNameLower());
        $sourcePath = module_path($this->getModuleName(), 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->getModuleNameLower().'-module-views']);

        $path = array_merge(
            // First try to load the published views
            [$viewPath],
            // Then try to load the module views
            $this->getPublishableViewPaths(),
            [$sourcePath],
        );

        $this->loadViewsFrom($path, $this->getModuleNameLower());

        Blade::componentNamespace(config('modules.namespace').'\\'.$this->getModuleName().'\\View\\Components', $this->getModuleNameLower());
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
        foreach ($this->provides() as $provider) {
            $this->app->register($provider);
        }

        $this->registerEvents();
    }

    public function registerRequiredModules(): void
    {
        $this->bootstrapModules();

        $this->registerEnabledModulesMigrationPaths();

        return;

        $modules = $this->requireModules();
        if (empty($modules)) {
            return;
        }
        if (! $this->app->runningInConsole()) {
            return;
        }
        $migrator = $this->app->make('migrator');
        foreach ($modules as $module) {
            $mod = Module::find($module);
            $mod->enable();
            $path = $mod->getPath().'/database/Migrations';
            if (is_dir($path)) {
                $migrator->path($path);
            }
        }

        return;
        Event::listen(CommandStarting::class, function (CommandStarting $event) use ($modules) {
            $cmd = $event->command ?? '';
            $migrationCommands = [
                'migrate',
                'migrate:fresh',
                'migrate:refresh',
                'migrate:install',
                'migrate:rollback',
                'migrate --force',
            ];
            if (empty($cmd)) {
                return;
            }
            if ($cmd === 'migrate'
                || str_starts_with($cmd, 'migrate:')
                || in_array($cmd, $migrationCommands, true)
            ) {
                $migrator = $this->app->make('migrator');
                foreach ($modules as $mod) {
                    $mod = Module::find($mod);
                    $mod->enable();
                    $path = $mod->getPath().'/database/Migrations';
                    dump($path);
                    if (is_dir($path)) {
                        $migrator->path($path);
                    }
                }
            }
        });
    }

    public function requireModules(): array
    {
        return [];
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

    protected function langPath(): string
    {
        return module_path($this->getModuleName(), 'lang');
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $this->publishes([module_path($this->getModuleName(), 'config/config.php') => config_path($this->getModuleNameLower().'.php')], 'config');
        $this->mergeConfigFrom(module_path($this->getModuleName(), 'config/config.php'), $this->getModuleNameLower());
    }

    protected function registerComponents(): void {}

    protected function registerEvents(): void {}

    protected function registerEnabledModulesMigrationPaths(): void
    {
        if (! $this->app->bound('migrator')) {
            return;
        }
        /** @var Migrator $migrator */
        $migrator = $this->app->make('migrator');

        foreach (Module::allEnabled() as $mod) {
            $path = module_path($mod->getName(), 'database/Migrations');
            if (is_dir($path)) {
                $migrator->path($path);
            }
        }
    }

    protected function collectRequiredModulesFromCurrentProvider(): array
    {
        $required = $this->requireModules();

        // Limpeza
        $required = array_values(array_unique(array_filter(array_map('trim', $required))));

        return $required;
    }

    protected function bootstrapModules(): void
    {
        $modules = $this->requireModules();

        foreach ($modules as $dep) {
            $dep = trim((string) $dep);
            if ($dep === '' || ! Module::has($dep)) {
                continue;
            }
            $module = Module::find($dep);
            $this->bootstrapModule($module);
        }
    }

    protected function bootstrapModule(\Nwidart\Modules\Module $moduleName): void
    {
        // 1) Habilita se estiver desabilitado (estado persistente)
        if ($moduleName->isDisabled()) {
            $moduleName->enable();
        }

        // 2) Evita reentrância neste processo
        if (isset(self::$bootstrapped[$moduleName->getName()])) {
            return;
        }
        self::$bootstrapped[$moduleName->getName()] = true;

        // 3) Registra os Service Providers declarados no module.json do módulo
        $moduleProviders = $moduleName->json('module.json')->get('modules_dependencies', []);
        foreach ($moduleProviders as $providerClass) {
            if (! class_exists($providerClass)) {
                continue;
            }
            // Evita registrar o mesmo provider duas vezes
            if (method_exists($this->app, 'providerIsLoaded') && $this->app->providerIsLoaded($providerClass)) {
                continue;
            }
            $this->app->register($providerClass);
        }

        // 4) Opcional: ao bootstrapar um módulo, já garantimos os paths de migração
        $this->registerEnabledModulesMigrationPaths();
    }

    protected function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->getModuleNameLower())) {
                $paths[] = $path.'/modules/'.$this->getModuleNameLower();
            }
        }

        return $paths;
    }

    private function configureCommands(): void
    {
        DB::prohibitDestructiveCommands(
            $this->app->isProduction()
        );
    }
}
