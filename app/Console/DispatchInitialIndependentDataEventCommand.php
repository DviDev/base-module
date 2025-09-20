<?php

declare(strict_types=1);

namespace Modules\Base\Console;

use Event;
use Illuminate\Console\Command;
use Modules\Base\Events\BaseSeederInitialIndependentDataEvent;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

final class DispatchInitialIndependentDataEventCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'base:dispatch_seed_initial_data_event';

    /**
     * The console command description.
     */
    protected $description = 'Command description.';

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
        Event::dispatch(new BaseSeederInitialIndependentDataEvent);
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
