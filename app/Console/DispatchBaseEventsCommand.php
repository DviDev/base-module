<?php

namespace Modules\Base\Console;

use Illuminate\Console\Command;
use Modules\Base\Events\DatabaseSeederEvent;
use Modules\Base\Events\SeederFinishedEvent;
use Modules\DBMap\Events\ScanTableEvent;

class DispatchBaseEventsCommand extends Command
{
    protected $signature = 'base:dispatch_base_events';

    protected $description = 'Dispatch Base Events Command';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        \Event::dispatch(new ScanTableEvent);
        \Event::dispatch(new DatabaseSeederEvent());
        \Event::dispatch(new SeederFinishedEvent);
    }
}
