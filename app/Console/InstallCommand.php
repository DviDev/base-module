<?php

namespace Modules\Base\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Modules\Base\Events\DatabaseSeederEvent;
use Modules\Base\Events\SeederFinishedEvent;
use Modules\DBMap\Events\ScanTableEvent;
use function Laravel\Prompts\spin;

class InstallCommand extends Command
{
    protected $signature = 'base:install';
    protected $description = 'Install application.';

    public function handle()
    {
        $collection = collect([
            'migrate:fresh' => ['type' => 'command'],
            'base:dispatch_seed_initial_data_event' => ['type' => 'command'],

            //'base:dispatch_base_events'
            ['type' => 'event', 'class' => ScanTableEvent::class],
            ['type' => 'event', 'class' => DatabaseSeederEvent::class],
            ['type' => 'event', 'class' => SeederFinishedEvent::class],

            'db:seed' => ['type' => 'command'],
        ]);

        $this->withProgressBar($collection, function ($item, $bar, $key) {
            if ($item['type'] == 'command') {
                spin(fn() => Artisan::call($key), '🤖  Running: ' . $key);
                return;
            }
            if ($item['type'] == 'event') {
                $this->info(PHP_EOL . '🤖  Dispatching: ' . $item['class']);
                $class = $item['class'];
                \Event::dispatch(new $class);
            }
        });
    }
}
