<?php

namespace Modules\Base\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Modules\App\Database\Seeders\ConfigTableSeeder;
use Modules\App\Entities\User\UserType;
use Modules\DBMap\Database\Seeders\DBMapDatabaseSeeder;
use Modules\Permission\Database\Seeders\PermissionTeamsTableSeeder;
use Modules\Post\Models\PostCommentModel;
use Modules\Post\Models\PostModel;
use Modules\Project\Models\ProjectModel;
use Modules\ViewStructure\Database\Seeders\ViewStructureDatabaseSeeder;
use Modules\Workspace\Database\Seeders\WorkspaceTableSeeder;
use Modules\Workspace\Models\WorkspaceModel;
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

        if ($modules->contains('DBMap')) {
            $this->call(DBMapDatabaseSeeder::class);
        }
        if ($modules->contains('ViewStructure')) {
            $this->call(ViewStructureDatabaseSeeder::class);
        }
        $this->call(PermissionTeamsTableSeeder::class);
        $this->call(ConfigTableSeeder::class);

        $superAdmin = User::query()->where('type', UserType::SUPER_ADMIN->value)->first();
        ProjectModel::factory()
            ->for($superAdmin)
            ->create(['name' => config('app.name')]);

        $this->call(WorkspaceTableSeeder::class);

        /**@var \Nwidart\Modules\Laravel\Module $module */
        foreach ($modules as $module) {
            if (in_array($module->getName(), ['Base', 'DBMap'])) {
                continue;
            }
            $this->call('Modules\\' . $module->getName() . '\\Database\\Seeders\\' . $module->getName() . 'DatabaseSeeder');
        }
    }
}
