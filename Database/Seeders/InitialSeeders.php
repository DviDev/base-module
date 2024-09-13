<?php

namespace Modules\Base\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Modules\Base\Events\BaseSeederInitialIndependentDataEvent;
use Modules\Base\Events\DatabaseSeederEvent;
use Modules\Base\Events\SeederFinishedEvent;
use Modules\DBMap\Events\ScanTableEvent;
use Modules\Project\Models\ElementTypeModel;
use Nwidart\Modules\Facades\Module;

class InitialSeeders extends BaseSeeder
{
    public function run($modules = null): void
    {
        Model::unguard();

        cache()->clear();

        $storage_path = storage_path('app/temp_seed_files');
        File::deleteDirectory($storage_path);

        $modules = $modules ?: collect(Module::allEnabled());

        $this->seed($modules);
    }

    protected function createFirstProjectElementTypes(): void
    {
        $this->command->info('Creating Element Type Model');
        ElementTypeModel::query()->create(['name' => 'user type']);
        ElementTypeModel::query()->create(['name' => 'attribute']);
    }

    protected function seed(Collection $modules): void
    {
        event(new BaseSeederInitialIndependentDataEvent($this->command));
        event(new ScanTableEvent($this->command));
        event(new DatabaseSeederEvent($this->command));
        event(new SeederFinishedEvent($this->command));
    }
}
