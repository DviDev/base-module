<?php

namespace Modules\Base\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Modules\Base\Events\BaseSeederInitialIndependentDataEvent;
use Modules\Base\Events\CallAppDatabaseSeederEvent;
use Modules\Base\Events\CallPermissionDatabaseSeederEvent;
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
//        $this->call(ProjectInitialDataSeeder::class);
        event(new BaseSeederInitialIndependentDataEvent($this->command));

        if ($modules->contains('DBMap')) {
            event(new ScanTableEvent($this->command));
//            event(new CallDBMapDatabaseSeederEvent());
            $modules = $modules->except('DBMap');
        }
        if ($modules->contains('View')) {
//            event(new CallViewDatabaseSeederEvent());
            $modules = $modules->except('View');
        }
        if ($modules->contains('App')) {
            event(new CallAppDatabaseSeederEvent);
            $modules = $modules->except('App');
        }
        if ($modules->contains('Permission')) {
            event(new CallPermissionDatabaseSeederEvent());
            $modules = $modules->except('Permission');
        }

        /**@var \Nwidart\Modules\Laravel\Module $module */
        foreach ($modules as $module) {
            if (in_array($module->getName(), [
                'Base',
                'App',
                'DBMap',
            ])) {
                continue;
            }
            /*$scan_seeder_class = 'Modules\\' . $module->getName() . '\\Database\\Seeders\\Scan' . $module->getName() . 'ModuleSeeder';
            if (File::exists(base_path($scan_seeder_class))) {
                $this->call($scan_seeder_class);
            }*/

            $this->call('Modules\\' . $module->getName() . '\\Database\\Seeders\\' . $module->getName() . 'DatabaseSeeder');
        }
        event(new SeederFinishedEvent($this->command));
    }
}
