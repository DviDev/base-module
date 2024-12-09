<?php

namespace Modules\Base\Console;

use Illuminate\Console\Command;
use Modules\Base\Events\SeederFinishedEvent;
use Modules\DBMap\Events\ScanTableEvent;

class DispatchBaseEventsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'base:dispatch_base_events';
    /**
     * The console command description.
     */
    protected $description = 'Dispatch Base Events Command';

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
        \Event::dispatch(new ScanTableEvent());
//        \Event::dispatch(new DatabaseSeederEvent());
        \Event::dispatch(new SeederFinishedEvent());
    }
}
