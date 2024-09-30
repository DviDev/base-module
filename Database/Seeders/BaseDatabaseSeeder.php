<?php

namespace Modules\Base\Database\Seeders;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Modules\Base\Events\BaseSeederInitialIndependentDataEvent;
use Modules\Base\Events\DatabaseSeederEvent;
use Modules\Base\Events\SeederFinishedEvent;
use Modules\DBMap\Events\ScanTableEvent;

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

        event(new BaseSeederInitialIndependentDataEvent($this->command));
        event(new ScanTableEvent($this->command));
        event(new DatabaseSeederEvent($this->command));
        event(new SeederFinishedEvent($this->command));

        $this->commandInfo(__CLASS__, '🟢 done');
    }
}
