<?php

namespace Modules\Base\Database\Seeders;

use Nwidart\Modules\Facades\Module;

class SecondSeeders extends BaseSeeder
{
    public function run()
    {
        $modules = collect(Module::allEnabled());
        /**@var \Nwidart\Modules\Laravel\Module $module */
        foreach ($modules as $module) {
            if (in_array($module->getName(), ['Base', 'App', 'DBMap', 'Seguro'])) {
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
