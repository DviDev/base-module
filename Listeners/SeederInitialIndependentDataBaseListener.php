<?php

namespace Modules\Base\Listeners;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Modules\App\Database\Seeders\ConfigTableSeeder;
use Modules\Base\Events\BaseSeederInitialIndependentDataEvent;
use Modules\Base\Models\RecordTypeModel;
use Nwidart\Modules\Facades\Module;

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
        $this->command = $event->command;
        $this->command->info(PHP_EOL . '🤖 Base Module: Creating initial data');
        $this->createModuleRecordTypes();

        Artisan::call('db:seed', ['--class' => ConfigTableSeeder::class]);

        $event->command->info(PHP_EOL . '🤖 Base Module: Initial data created');
    }

    protected function createModuleRecordTypes(): void
    {
        $this->command->info(PHP_EOL . '🤖 Base Module: Creating Record Types');
        foreach (Module::all() as $module) {
            RecordTypeModel::factory()->create(['name' => $module]);
        }
    }
}
