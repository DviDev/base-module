<?php

namespace Modules\Base\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use function Laravel\Prompts\multiselect;

class InstallModulesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'base:install-modules';

    /**
     * The console command description.
     */
    protected $description = 'Install Modules.';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $modules = [
            'App' => 'DviDev/app-module',
            'AppBuilder' => 'DviDev/appbuilder-module',
            'Chat' => 'DviDev/chat-module',
            'DBMap' => 'DviDev/dbmap-module',
            'DvUi' => 'DviDev/dvui-module',
            'Flowbite' => 'DviDev/flowbite-module',
            'Insur' => 'DviDev/insur-module',
            'Link' => 'DviDev/link-module',
            'Lte' => 'DviDev/adminlte_blade-module',
            'MercadoPago' => 'DviDev/mercado_pago-module',
            'Permission' => 'DviDev/permission-module',
            'Person' => 'DviDev/person-module',
            'Post' => 'DviDev/post-module',
            'Project' => 'DviDev/project-module',
            'Social' => 'DviDev/social-module',
            'Solicitation' => 'DviDev/solicitation-module',
            'Store' => 'DviDev/store-module',
            'Task' => 'DviDev/task-module',
            'View' => 'DviDev/view_structure-module',
            'Workspace' => 'DviDev/workspace-module',
        ];

        $modules = multiselect(
            label: 'What permissions should be assigned?',
            options: array_keys($modules)
        );
        if (count($modules) == 0) {
            return;
        }

        exec('sail composer require nwidart/laravel-modules');
        exec('sail php artisan vendor:publish --provider="Nwidart\Modules\LaravelModulesServiceProvider"');

        foreach ($modules as $module => $vendor) {
            $this->info(__('installing') . ' module: '. $module);
            exec("cd Modules git clone git@github.com:$module.git $module && cd $module && git flow init -d && cd ../../ && sa module:enable $module && cd Modules");
        }
        $this->info('');
    }

    /**
     * Get the console command arguments.
     */
    protected function getArguments(): array
    {
        return [
            ['example', InputArgument::REQUIRED, 'An example argument.'],
        ];
    }

    /**
     * Get the console command options.
     */
    protected function getOptions(): array
    {
        return [
            ['example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null],
        ];
    }
}
