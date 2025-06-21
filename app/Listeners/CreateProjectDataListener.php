<?php

namespace Modules\Base\Listeners;

use Modules\Base\Events\SeederFinishedEvent;

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
        /* app()->instance('module_name', 'Base');
        Artisan::call("db:seed", [
            '--class' => ProjectTableSeeder::class,
        ]); */
    }
}
