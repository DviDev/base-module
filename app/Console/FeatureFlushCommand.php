<?php

declare(strict_types=1);

namespace Modules\Base\Console;

use DB;
use Illuminate\Console\Command;
use Laravel\Pennant\Feature;
use Symfony\Component\Console\Input\InputOption;

final class FeatureFlushCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'feature:flush';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Feature flush command.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): void
    {
        DB::table('features')->truncate();
        Feature::flushCache();
    }

    /**
     * Get the console command arguments.
     */
    protected function getArguments(): array
    {
        return [
            //            ['example', InputArgument::REQUIRED, 'An example argument.'],
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
