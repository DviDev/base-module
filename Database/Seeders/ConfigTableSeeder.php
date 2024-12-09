<?php

namespace Modules\Base\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Modules\Base\Models\RecordTypeModel;
use Nwidart\Modules\Facades\Module;

class ConfigTableSeeder extends BaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $this->command->warn(PHP_EOL . '🤖 🌱 seeding ' . str(__CLASS__)->explode('\\')->last() . ' ...');

        $this->createModuleRecordTypes();

        $this->commandInfo(__CLASS__, '🟢 done');
    }

    protected function createModuleRecordTypes(): void
    {
        $this->command->info(PHP_EOL . '🤖 Base Module: Creating Record Types');
        foreach (Module::all() as $module) {
            RecordTypeModel::factory()->create(['name' => $module]);
        }
    }
}
