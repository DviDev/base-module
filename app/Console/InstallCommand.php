<?php

namespace Modules\Base\Console;

use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Modules\Base\Events\BaseSeederInitialIndependentDataEvent;
use Modules\Base\Events\DatabaseSeederEvent;
use Modules\DBMap\Events\ScanTableEvent;
use Modules\Person\Services\SeedFirstOrCreateUser;
use Throwable;

class InstallCommand extends Command
{
    protected $signature = 'base:install';

    protected $description = 'Install application.';

    public function handle()
    {

        new SeedFirstOrCreateUser()->createUserTypes();
        event(new BaseSeederInitialIndependentDataEvent);

        Bus::batch([
            function () {
                event(new ScanTableEvent);
            },
        ])
            ->then(function (Batch $batch) {
                event(new DatabaseSeederEvent);
            })->catch(function (Batch $batch, Throwable $e) {
                throw $e;
            })->dispatch();
    }
}
