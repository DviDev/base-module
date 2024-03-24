<?php

namespace Modules\Base\Database\Seeders;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Modules\App\Database\Seeders\AppDatabaseSeeder;
use Modules\DBMap\Database\Seeders\DBMapDatabaseSeeder;
use Modules\Permission\Database\Seeders\PermissionTeamsTableSeeder;
use Modules\Project\Models\ElementTypeModel;
use Modules\View\Database\Seeders\ViewDatabaseSeeder;
use Modules\Workspace\Database\Seeders\WorkspaceTableSeeder;
use Nwidart\Modules\Facades\Module;

class BaseDatabaseSeeder extends BaseSeeder
{
//    use WithoutModelEvents;
    /**
     * @throws Exception
     */
    public function run(): void
    {
        Model::unguard();

        cache()->clear();

        $storage_path = storage_path('app/temp_seed_files');
        File::deleteDirectory($storage_path);

        $modules = collect(Module::allEnabled());

        if ($modules->contains('Project')) {
            $this->command->info('Creating Element Type Model');
            ElementTypeModel::query()->create(['name' => 'user type']);
            ElementTypeModel::query()->create(['name' => 'attribute']);
        }

        if ($modules->contains('DBMap')) {
            $this->call(DBMapDatabaseSeeder::class);
        }

        if ($modules->contains('View')) {
            $this->call(ViewDatabaseSeeder::class);
        }
        if ($modules->contains('App')) {
            $this->call(AppDatabaseSeeder::class);
        }
        try {
            $this->seed($modules);

            $this->commandInfo(__CLASS__, '🟢 done');
        } catch (Exception $exception) {
            $this->command->error('🤖 Error when seeding, try again.');
            throw $exception;
        }
    }

    protected function seed($modules): void
    {
        if ($modules->contains('Permission')) {
            $this->call(PermissionTeamsTableSeeder::class);
        }
        if ($modules->contains('Workspace')) {
            $this->call(WorkspaceTableSeeder::class);
        }

        /**@var \Nwidart\Modules\Laravel\Module $module */
        foreach ($modules as $module) {
            if (in_array($module->getName(), ['Base', 'App', 'DBMap'])) {
                continue;
            }
            /*$scan_seeder_class = 'Modules\\' . $module->getName() . '\\Database\\Seeders\\Scan' . $module->getName() . 'ModuleSeeder';
            if (File::exists(base_path($scan_seeder_class))) {
                $this->call($scan_seeder_class);
            }*/
            $this->call('Modules\\' . $module->getName() . '\\Database\\Seeders\\' . $module->getName() . 'DatabaseSeeder');
        }
    }
}
