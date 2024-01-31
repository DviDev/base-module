<?php

namespace Modules\Base\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Modules\App\Database\Seeders\AppDatabaseSeeder;
use Modules\DBMap\Database\Seeders\DBMapDatabaseSeeder;
use Modules\Permission\Database\Seeders\PermissionTeamsTableSeeder;
use Modules\Project\Models\ElementTypeModel;
use Modules\Project\Models\ProjectModel;
use Modules\View\Database\Seeders\ViewDatabaseSeeder;
use Modules\Workspace\Database\Seeders\WorkspaceTableSeeder;
use Nwidart\Modules\Facades\Module;

class BaseDatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        cache()->clear();

        $modules = collect(Module::allEnabled());

        $this->command->info('Creating Element Type Model');
        ElementTypeModel::query()->create(['name' => 'user type']);
        ElementTypeModel::query()->create(['name' => 'attribute']);

        if ($modules->contains('DBMap')) {
            $this->call(DBMapDatabaseSeeder::class);
        }
        if ($modules->contains('View')) {
            $this->call(ViewDatabaseSeeder::class);
        }
        if ($modules->contains('Permission')) {
            $this->call(PermissionTeamsTableSeeder::class);
        }
        if ($modules->contains('App')) {
            $this->call(AppDatabaseSeeder::class);
        }
        if ($modules->contains('Project')) {
            $developer = User::query()->where('type_id', 1)->first();
            ProjectModel::firstOrCreate(['owner_id' => $developer->id, 'name' => config('app.name')]);
        }
        if ($modules->contains('Workspaces')) {
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
