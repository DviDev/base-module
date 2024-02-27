<?php

namespace Modules\Base\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Modules\App\Database\Seeders\AppDatabaseSeeder;
use Modules\DBMap\Database\Seeders\DBMapDatabaseSeeder;
use Modules\Permission\Database\Seeders\PermissionTeamsTableSeeder;
use Modules\Project\Models\ElementTypeModel;
use Modules\Project\Models\ProjectModel;
use Modules\View\Database\Seeders\ViewDatabaseSeeder;
use Modules\Workspace\Database\Seeders\WorkspaceTableSeeder;
use Nwidart\Modules\Facades\Module;

class BaseDatabaseSeeder extends BaseSeeder
{
//    use WithoutModelEvents;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
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
            DB::beginTransaction();

            $this->seed($modules);

            DB::commit();

            $this->commandInfo(__CLASS__, '🟢 done');
        } catch (\Exception $exception) {
            DB::rollBack();

            $this->command->error('🤖 Error when seeding, try again.');
            throw $exception;
        }
    }

    protected function seed($modules)
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
            $this->call('Modules\\' . $module->getName() . '\\Database\\Seeders\\' . $module->getName() . 'DatabaseSeeder');
        }
    }
}
