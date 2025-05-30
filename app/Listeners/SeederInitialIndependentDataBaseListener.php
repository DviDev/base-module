<?php

namespace Modules\Base\Listeners;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Modules\Base\Database\Seeders\ConfigTableSeeder;
use Modules\Base\Events\BaseSeederInitialIndependentDataEvent;

class SeederInitialIndependentDataBaseListener
{
    private Command $command;

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
    public function handle(BaseSeederInitialIndependentDataEvent $event): void
    {
        Artisan::call('db:seed', ['--class' => ConfigTableSeeder::class]);
    }
}
