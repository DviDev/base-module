<?php

namespace Modules\Base\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Modules\Base\Jobs\DatabaseSeederJob;
use Modules\Base\Jobs\InstallFinishedJob;
use Modules\Base\Jobs\SeederInitialIndependentDataJob;
use Modules\DBMap\Jobs\ScanTableJob;
use Modules\Person\Services\SeedFirstOrCreateUser;
use Modules\Project\Jobs\DefineEntityAttributeRelationJob;

class InstallCommand extends Command
{
    protected $signature = 'base:install';

    protected $description = 'Install application.';

    public function handle()
    {
        (new SeedFirstOrCreateUser)->createUserTypes();

        Bus::chain([
            function () {
                Log::info('Instalação iniciada.');
            },
            new SeederInitialIndependentDataJob,
            new ScanTableJob,
            new DatabaseSeederJob,
            new InstallFinishedJob,
            new DefineEntityAttributeRelationJob,
        ])
            ->dispatch();
    }
}
