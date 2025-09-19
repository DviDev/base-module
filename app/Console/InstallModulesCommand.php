<?php

declare(strict_types=1);

namespace Modules\Base\Console;

use Exception;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\pause;

final class InstallModulesCommand extends Command
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
    public function handle(): void
    {
        $modules = config('base.modules');

        $choices = multiselect(
            label: __('Which modules do you want to install?'),
            options: array_keys($modules)
        );
        if (count($choices) === 0) {
            return;
        }
        pause('Installing '.collect($choices)->join(',', 'and').'. Press enter to continue');

        if ($this->confirm('You want install nwidart/laravel-modules?')) {
            exec('composer require nwidart/laravel-modules');
            exec('php artisan vendor:publish --provider="Nwidart\Modules\LaravelModulesServiceProvider"');
        }
        foreach ($choices as $module) {
            $vendor = $modules[$module];
            $this->info(PHP_EOL.'🤖 '.$vendor.' '.__('installing'));

            if ($this->alreadyInstalled($vendor, $module)) {
                continue;
            }
            $this->cloningModule($vendor, $module);
            $this->initializeGitFlow($module);
            $this->enableModule($module);
            $this->composerDumpAutoload();

            $this->info(PHP_EOL.'🤖 '.$vendor.' (✔ installed)');
        }
        $this->info(PHP_EOL.'🤖 '.__('done'));
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

    protected function initializeGitFlow(int|string $module): void
    {
        if (! confirm('Initialize git flow?')) {
            return;
        }
        exec("cd Modules/$module && git flow init -d");
    }

    protected function enableModule(int|string $module): void
    {
        if (! confirm('Can you enable module now?')) {
            return;
        }
        $enable_command = 'php artisan module:enable '.$module;
        pause('RUNNING: '.$enable_command.' Continue? (enter to continue)');
        $output = [];
        exec($enable_command, $output);
        foreach (collect($output) as $line) {
            $this->info($line);
        }
    }

    protected function cloningModule(mixed $vendor, int|string $module): void
    {
        $command = "git clone https://github.com/$vendor.git Modules/$module";
        pause($command);
        $output = '';
        exec($command, $output);
        if ($output) {
            $this->error($output);
            throw new Exception($output);
        }
    }

    protected function alreadyInstalled(mixed $vendor, int|string $module): bool
    {
        $installed = [];
        exec('cd Modules && ls', $installed);
        if (! in_array($module, $installed)) {
            return false;
        }
        $this->info(PHP_EOL.'🤖 '.__('already installed').' '.$vendor);

        return true;
    }

    protected function composerDumpAutoload(): void
    {
        $output = [];
        $command = 'composer dump-autoload';
        pause('RUNNING: '.$command.' - Continue? (enter to continue)');
        exec($command, $output);
        foreach (collect($output) as $line) {
            $this->info($line);
        }
    }
}
