<?php

namespace Modules\Base\Listeners;

use Modules\Base\Events\SeederFinishedEvent;
use Modules\Project\Database\Seeders\ProjectTableSeeder;

class SeederFinishedBaseListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(SeederFinishedEvent $event): void
    {
        $seeder = new ProjectTableSeeder();
        $seeder->run(module_name: 'Base', command: $event->command);
    }
}
