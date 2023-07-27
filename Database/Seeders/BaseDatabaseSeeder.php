<?php

namespace Modules\Base\Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Modules\DBMap\Database\Seeders\DBMapDatabaseSeeder;
use Modules\ViewStructure\Database\Seeders\ViewStructureDatabaseSeeder;
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

        if ($modules->contains('DBMap') && $modules->contains('ViewStructure')) {
            $this->call(DBMapDatabaseSeeder::class);
            $this->call(ViewStructureDatabaseSeeder::class);
        }

        /**@var \Nwidart\Modules\Laravel\Module $module */
        foreach ($modules as $module) {
            if (in_array($module->getName(), ['Base', 'DBMap'])) {
                continue;
            }
            $this->call('Modules\\' . $module->getName() . '\\Database\\Seeders\\' . $module->getName() . 'DatabaseSeeder');
        }
    }
}
