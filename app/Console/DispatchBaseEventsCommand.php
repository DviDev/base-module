<?php

declare(strict_types=1);

namespace Modules\Base\Console;

use Event;
use Illuminate\Console\Command;
use Modules\Base\Events\DatabaseSeederEvent;
use Modules\DBMap\Events\ScanTableEvent;

final class DispatchBaseEventsCommand extends Command
{
    protected $signature = 'base:dispatch_base_events';

    protected $description = 'Dispatch Base Events Command';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        Event::dispatch(new ScanTableEvent);
        Event::dispatch(new DatabaseSeederEvent);
    }
}
