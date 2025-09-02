<?php

namespace Modules\Base\Listeners;

use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Artisan;
use Modules\Base\Database\Seeders\ConfigTableSeeder;
use Modules\Base\Events\BaseSeederInitialIndependentDataEvent;

class SeederInitialIndependentDataBaseListener implements ShouldQueue
{
    private Command $command;

    public function handle(BaseSeederInitialIndependentDataEvent $event): void
    {
        Artisan::call('db:seed', ['--class' => ConfigTableSeeder::class]);
    }
}
