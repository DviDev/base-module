<?php

namespace Modules\Base\Listeners;

use Modules\Base\Events\InstallFinishedEvent;

class CreateProjectDataListener
{
    public function handle(InstallFinishedEvent $event): void
    {
        /* app()->instance('module_name', 'Base');
        Artisan::call("db:seed", [
            '--class' => ProjectTableSeeder::class,
        ]); */
    }
}
