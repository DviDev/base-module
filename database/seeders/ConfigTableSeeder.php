<?php

namespace Modules\Base\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Modules\Base\Models\RecordTypeModel;
use Modules\Project\Models\ProjectModuleEntityDBModel;

class ConfigTableSeeder extends BaseSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Model::unguard();

        $this->command->warn(PHP_EOL.'🤖 🌱 seeding '.str(__CLASS__)->explode('\\')->last().' ...');

        $this->createModuleRecordTypes();

        $this->commandInfo(__CLASS__, '🟢 done');
    }

    protected function createModuleRecordTypes(): void
    {
        /*$this->command->info(PHP_EOL.'🤖 Base Module: Creating Record Types');
        foreach (ProjectModuleEntityDBModel::all() as $entity) {
            RecordTypeModel::factory()->create(['name' => $entity->title]);
        }*/
    }
}
