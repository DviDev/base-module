<?php

declare(strict_types=1);

namespace Modules\Base\Listeners;

use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Artisan;
use Modules\Base\Database\Seeders\ConfigTableSeeder;
use Modules\Base\Events\SeedInitialIndependentDataEvent;

final class SeederInitialIndependentDataBaseListener implements ShouldQueue
{
    private Command $command;

    public function handle(SeedInitialIndependentDataEvent $event): void
    {
        Artisan::call('db:seed', ['--class' => ConfigTableSeeder::class]);
    }
}
