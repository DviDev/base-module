<?php

namespace Modules\Base\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Modules\App\Database\Seeders\AppDatabaseSeeder;
use Modules\DBMap\Database\Seeders\DBMapDatabaseSeeder;
use Modules\Permission\Database\Seeders\PermissionTeamsTableSeeder;
use Modules\Project\Models\ElementTypeModel;
use Modules\View\Database\Seeders\ViewDatabaseSeeder;
use Modules\Workspace\Database\Seeders\WorkspaceTableSeeder;
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

        if ($modules->contains('Project')) {
            $this->createFirstProjectElementTypes();
        }

        $this->seed($modules);
    }

    protected function createFirstProjectElementTypes(): void
    {
        $this->command->info('Creating Element Type Model');
        ElementTypeModel::query()->create(['name' => 'user type']);
        ElementTypeModel::query()->create(['name' => 'attribute']);
    }

    protected function seed($modules): void
    {
        if ($modules->contains('DBMap')) {
            $this->call(DBMapDatabaseSeeder::class);
        }
        if ($modules->contains('View')) {
            $this->call(ViewDatabaseSeeder::class);
        }
        if ($modules->contains('App')) {
            $this->call(AppDatabaseSeeder::class);
        }
        if ($modules->contains('Permission')) {
            $this->call(PermissionTeamsTableSeeder::class);
        }
        if ($modules->contains('Workspace')) {
            $this->call(WorkspaceTableSeeder::class);
        }
    }
}
