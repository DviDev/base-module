<?php

namespace Modules\Base\Listeners;

use Modules\Base\Events\SeederFinishedEvent;
use Modules\Project\Database\Seeders\ProjectTableSeeder;

class CreateProjectDataListener
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
        (new ProjectTableSeeder())->run(module_name: 'Base');
    }
}
